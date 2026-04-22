<?php

class InclusiveAiDescriptions {

    //Variables
    private static $instance;
    private static $post_type = 'fotografia';
    private static $apiKey = 'a';
    private static $model = 'gpt-4o';

    //Function for singleton instances
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //Private constructor for singleton purposes
    private function __construct() {
        add_filter('acf/render_field', array($this, 'print_button'));

        add_action('wp_ajax_ai-gen-description', array($this, 'ai_gen_description'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_batch_page'));
        add_action('wp_ajax_ai-gen-description-batch', array($this, 'ai_gen_description'));
        add_action('wp_ajax_ai-save-batch-progress', array($this, 'save_batch_progress'));
        add_action('wp_ajax_ai-reset-batch-progress', array($this, 'reset_batch_progress'));
    }

    //Renders a button that generates an AI-based description for the image
    public function print_button($field) {
         if ($field['_name'] === 'descripcion_ia' && is_admin()) {
            echo '<br/><span id="saving-description" class="loader saving"></span>
                <button type="button" id="ai_description_button" name="ai_description_button"
                    class="button button-primary">Generar descripcion de imagen</button>';
        }
    }

    //Generates a description of the image
    public function ai_gen_description() {
        if (isset($_POST['post_id'])) {
            $post_id = intval($_POST['post_id']);
        }
        $imagen = get_post_thumbnail_id($post_id);
        $imgData = wp_get_attachment_image_src($imagen, 'large');
        $imgUrl = $imgData ? $imgData[0] : '';

        try {
            $url = 'https://api.openai.com/v1/chat/completions';
            $key = self::$apiKey;
            $body = '{
                    "model": "' . self::$model . '",
                    "temperature": 0.2,
                    "messages": [
                        {
                            "role": "system",
                            "content": "Eres una herramienta de descripcion de imagenes para personas ciegas. Las imagenes que recibes son de un concurso de fotografia sobre la vida con discapacidad y tu labor se corresponde a describirlas muy detalladamente para personas con problemas visuales. Si hay alguna persona con discapacidad, debes incluirlo en la descripcion y decir cual es: fisica, intelectual, Sindrome de Down (distinto que intelectual) auditiva, visual, paralisis cerebral, sordoceguera, problemas de lenguaje, esclerosis multiple, lesion cerebral, parkinson (distinto de cerebral) o mental (ej: autismo). Di tambien si la imagen corresponde a un retrato y el tema que aborda como trabajo, deporte, ocio, salud, accesibilidad, salud, rehabilitacion, educacion, empleo, servicios sociales, turismo, cultura, vivienda o ayudas tecnicas. Valora sobre todo la cara para determinar el tipo de discapacidad y en caso de no encontrar, valora el resto del cuerpo (si al cuerpo le falta algun miembro o tiene algun tipo de distrofia o posiciones no naturales de las articulaciones o una protesis (generalmente de distinto color a su piel)). En caso de encontrar una imagen explicita, haz como que tiene un bañador obviando este hecho. En el contexto de los deportes, di si lleva alguna adaptacion reglamentaria, pero sin detenerte a valorar cual. Si no ves indicios de discapacidad no lo digas."
                        },
                        {
                            "role": "user", "content": [
                            {
                                "type": "text",
                                "text": "Describe la siguiente imagen"
                            },
                            {
                                "type": "image_url",
                                "image_url": {
                                    "url": "' . $imgUrl . '"
                                }
                           }]
                        }
                    ]}';
            $response = $this->GPTRequest($url, $key, $body);

            if ($response && !empty($response)) {
                update_field('descripcion_ia', $response, $post_id);
                echo $response;
            } else {
                echo "No se ha podido generar la descripción.";
            }

        } catch (\Exception $e) {
            echo "No se ha podido procesar la imagen. Inténtelo en unos minutos.";
        }
        exit();
    }

    //Makes a request for GPT to describe the image
    public function GPTRequest($url, $key, $body) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $key
            ),
        ));

        $response = curl_exec($curl);
        $jsonResponse = json_decode($response, true);
        if (isset($jsonResponse['error'])) {
            $result = $jsonResponse['error']['message'];
        } else {
            $result = $jsonResponse['choices'][0]['message']['content'];
        }
        curl_close($curl);

        return $result;
    }

    // Saves batch progress
    public function save_batch_progress() {
        check_ajax_referer('ai_save_batch', 'nonce');
        
        if (!isset($_POST['processed']) || !is_array($_POST['processed'])) {
            wp_send_json_error('Datos de procesados no válidos');
        }
        
        $processed_posts = array_map('intval', $_POST['processed']);
        
        $progress = array(
            'processed' => $processed_posts,
            'current_index' => isset($_POST['current_index']) ? intval($_POST['current_index']) : 0,
            'successful' => isset($_POST['successful']) ? intval($_POST['successful']) : 0,
            'failed' => isset($_POST['failed']) ? intval($_POST['failed']) : 0,
            'last_update' => current_time('timestamp')
        );
        
        update_option('ai_batch_progress', $progress, false);
        wp_send_json_success(array('saved' => count($processed_posts)));
    }

    // Resets batch progress
    public function reset_batch_progress() {
        check_ajax_referer('ai_reset_batch', 'nonce');
        
        delete_option('ai_batch_progress');
        wp_send_json_success();
    }

    // Enqueue the JavaScript file only when we're on the post edit screen
    public function enqueue_scripts() {
        if (is_admin()) {
            wp_register_style('ai-css', plugins_url('inclusive-ai-descriptions/css/loader.css'));
            wp_enqueue_style('ai-css');

            wp_enqueue_script('ai-js', plugins_url('inclusive-ai-descriptions/js/inclusive_ai.js'), array('jquery'), '1.0', true);
        }
    }

    // Adds an admin page for batch processing
    public function add_batch_page() {
        add_submenu_page(
            'edit.php?post_type=fotografia',
            'Generar Descripciones IA en Lote',
            'Generar IA en Lote',
            'manage_options',
            'ai-batch-descriptions',
            array($this, 'render_batch_page')
        );
    }

    // Renders the batch processing page
    public function render_batch_page() {
        ?>
        <div class="wrap">
            <h1>Generar Descripciones IA en Lote</h1>
            
            <?php
            $all_args = array(
                'post_type' => 'fotografia',
                'posts_per_page' => -1,
                'post_status' => array('publish')
            );
            $all_posts = get_posts($all_args);
            
            $empty_args = array(
                'post_type' => 'fotografia',
                'posts_per_page' => -1,
                'post_status' => array('publish'),
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'descripcion_ia',
                        'value' => '',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'descripcion_ia',
                        'compare' => 'NOT EXISTS'
                    )
                )
            );
            
            $empty_posts = get_posts($empty_args);
            
            $processable_posts = array();
            $no_featured = array();
            
            foreach($empty_posts as $post) {
                if(has_post_thumbnail($post->ID)) {
                    $processable_posts[] = $post->ID;
                } else {
                    $no_featured[] = $post;
                }
            }
                
            $total_all = count($all_posts);
            $total_empty = count($empty_posts);
            $total_with_featured = count($processable_posts);
            
            // Obtener progreso guardado de la base de datos
            $saved_progress = get_option('ai_batch_progress', array(
                'processed' => array(),
                'current_index' => 0,
                'successful' => 0,
                'failed' => 0,
                'start_time' => null,
                'last_update' => null
            ));
            
            // Filtrar posts ya procesados
            $remaining_posts = array_diff($processable_posts, $saved_progress['processed']);
            $total_remaining = count($remaining_posts);
            $total_processed = count($saved_progress['processed']);
            ?>
            
            <h2>Estadísticas (Solo publicadas)</h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong>Total de fotografías:</strong></td>
                        <td><?php echo $total_all; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Fotografías sin descripción IA:</strong></td>
                        <td><?php echo $total_empty; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Con imagen destacada (procesables):</strong></td>
                        <td><?php echo $total_with_featured; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Sin imagen destacada:</strong></td>
                        <td><?php echo count($no_featured); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Ya procesadas:</strong></td>
                        <td id="already-processed"><?php echo $total_processed; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Restantes por procesar:</strong></td>
                        <td id="remaining-to-process" style="font-weight: bold; color: #0073aa;"><?php echo $total_remaining; ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if($total_with_featured > 0): ?>
                <div id="batch-process-container" style="margin-top: 20px;">
                    <h3>Procesamiento en Lote</h3>
                    <p>
                        Se generarán descripciones IA para <strong><?php echo $total_remaining; ?></strong> fotografías 
                        (ya se procesaron <?php echo $total_processed; ?> de <?php echo $total_with_featured; ?>).
                    </p>
                    
                    <div style="margin: 15px 0; padding: 10px; background: #f0f6fc; border-left: 4px solid #0073aa;">
                        <p><strong>⚠️ Importante:</strong> No cierres ni recargues esta página durante el procesamiento.</p>
                    </div>
                    
                    <button type="button" id="start-batch-process" class="button button-primary button-large">
                        <?php echo $total_processed > 0 ? 'Continuar Procesamiento' : 'Iniciar Procesamiento en Lote'; ?>
                    </button>
                    
                    <button type="button" id="stop-batch-process" class="button button-secondary" style="display: none;">
                        Pausar Procesamiento
                    </button>
                    
                    <button type="button" id="reset-batch-process" class="button button-link" 
                            style="margin-left: 10px;<?php echo $total_processed == 0 ? 'display:none;' : ''; ?>">
                        Reiniciar desde cero
                    </button>
                    
                    <div id="batch-progress" style="margin-top: 20px; display: none;">
                        <div class="progress-bar" style="width: 100%; background-color: #f1f1f1; border-radius: 3px;">
                            <div id="progress-bar-inner" style="width: 0%; height: 20px; background-color: #0073aa; border-radius: 3px; transition: width 0.3s;"></div>
                        </div>
                        <p id="progress-text">Procesando: 0/<?php echo $total_remaining; ?></p>
                        <p id="progress-status">Esperando inicio...</p>
                        <p id="progress-time">Tiempo transcurrido: 00:00:00 | Tiempo estimado restante: --:--:--</p>
                        
                        <div id="batch-results" style="margin-top: 20px; max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;"></div>
                    </div>
                    
                    <div id="completed-message" class="notice notice-success" style="margin-top: 20px; <?php echo $total_remaining == 0 ? '' : 'display: none;'; ?>">
                        <p>✅ <strong>¡Todo completado!</strong> Todas las <?php echo $total_with_featured; ?> fotografías han sido procesadas.</p>
                        <p><a href="javascript:location.reload()">Recargar página</a> para ver estadísticas actualizadas.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p>¡Todas las fotografías ya tienen descripción IA o no tienen imagen destacada!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            const allPosts = <?php echo json_encode($processable_posts); ?>;
            const alreadyProcessed = <?php echo json_encode($saved_progress['processed']); ?>;
            const savedIndex = <?php echo intval($saved_progress['current_index']); ?>;
            
            // Filter posts already processed
            const postsToProcess = allPosts.filter(postId => !alreadyProcessed.includes(postId));
            const totalPosts = postsToProcess.length;
            const totalAlreadyProcessed = alreadyProcessed.length;
            
            // Initialize variables
            let isProcessing = false;
            let currentIndex = 0;
            let successfulCount = 0;
            let failedCount = 0;
            let startTime = null;
            let timerInterval = null;

            const MS_TO_SECOND = 1000;
            const S_TO_MIN = 60;
            const S_TO_HOUR = 3600;
            const MS_PER_BATCH = 500;
            
            // Update counters from saved data
            successfulCount = <?php echo intval($saved_progress['successful']); ?>;
            failedCount = <?php echo intval($saved_progress['failed']); ?>;
            
            // Start/Continue button
            $('#start-batch-process').on('click', function() {
                if (totalPosts === 0) {
                    alert('No hay fotografías para procesar');
                    return;
                }
                
                isProcessing = true;
                currentIndex = 0;
                startTime = new Date();
                
                // Show UI
                $('#batch-progress').show();
                $('#start-batch-process').hide();
                $('#stop-batch-process').show();
                $('#reset-batch-process').hide();
                $('#batch-results').html('<div class="notice notice-info">Iniciando procesamiento por lotes...</div>');
                $('#progress-status').text('Preparando...');
                
                startTimer();
                setTimeout(processNextPost, MS_PER_BATCH);
            });
            
            // Pause
            $('#stop-batch-process').on('click', function() {
                alert('Procedimiento pausado, puedes continuar cuando quieras');
                
                isProcessing = false;
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                
                // Save progress
                saveProgress();
                
                $('#progress-status').html('<strong>Proceso pausado</strong>');
                $('#start-batch-process').show().text('Continuar Procesamiento');
                $('#stop-batch-process').hide();
                $('#reset-batch-process').show();
            });
            
            // Reset
            $('#reset-batch-process').on('click', function() {
                if (!confirm('¿Estás seguro de que quieres reiniciar desde cero?\nSe perderá todo el progreso guardado.')) {
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ai-reset-batch-progress',
                        nonce: '<?php echo wp_create_nonce("ai_reset_batch"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });
            
            // Init timer
            function startTimer() {
                if (timerInterval) clearInterval(timerInterval);
                
                timerInterval = setInterval(function() {
                    if (!startTime) return;
                    
                    const now = new Date();
                    const elapsed = Math.floor((now - startTime) / MS_TO_SECOND);
                    const AVG_SECONDS_PROCESS = 7; // 7 seconds to process a description for a post
                    
                    const processed = currentIndex;
                    const remaining = totalPosts - processed;
                    const avgTimePerItem = processed > 0 ? elapsed / processed : AVG_SECONDS_PROCESS;
                    const estimatedRemaining = Math.floor(remaining * avgTimePerItem);
                    
                    const elapsedStr = formatTime(elapsed);
                    const remainingStr = formatTime(estimatedRemaining);
                    
                    $('#progress-time').text(
                        'Tiempo transcurrido: ' + elapsedStr + 
                        ' | Tiempo estimado restante: ' + remainingStr
                    );
                }, MS_TO_SECOND);
            }
            
            // Format time
            function formatTime(seconds) {
                const hours = Math.floor(seconds / S_TO_HOUR);
                const minutes = Math.floor((seconds % S_TO_HOUR) / S_TO_MIN);
                const secs = seconds % S_TO_MIN;
                
                return [
                    hours.toString().padStart(2, '0'),
                    minutes.toString().padStart(2, '0'),
                    secs.toString().padStart(2, '0')
                ].join(':');
            }
            
            // Save progress
            function saveProgress() {
                const processedPosts = alreadyProcessed.concat(postsToProcess.slice(0, currentIndex));
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ai-save-batch-progress',
                        processed: processedPosts,
                        current_index: currentIndex,
                        successful: successfulCount,
                        failed: failedCount,
                        nonce: '<?php echo wp_create_nonce("ai_save_batch"); ?>'
                    }
                });
            }
            
            // Process a post
            function processNextPost() {
                if (!isProcessing) {
                    return;
                }
                
                if (currentIndex >= totalPosts) {
                    finishProcessing();
                    return;
                }
                
                const postId = postsToProcess[currentIndex];
                const progressPercent = ((currentIndex + 1) / totalPosts) * 100;
                const TIMEOUT = 1500;
                
                $('#progress-bar-inner').css('width', progressPercent + '%');
                $('#progress-text').text('Procesando: ' + (currentIndex + 1) + '/' + totalPosts);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'text',
                    data: {
                        action: 'ai-gen-description',
                        post_id: postId
                    },
                    success: function(response) {
                        let isError = false;
                        
                        // Verificar si es error
                        if (!response || 
                            response.includes('No se ha podido procesar') || 
                            response.includes('error') || 
                            response.includes('Error') ||
                            response.trim() === '') {
                            isError = true;
                            failedCount++;
                        } else {
                            successfulCount++;
                        }
                        
                        // Add result log in page
                        const resultDiv = $('<div>')
                            .css({
                                'margin': '5px 0',
                                'padding': '8px',
                                'border-left': '4px solid ' + (isError ? '#dc3232' : '#46b450'),
                                'background': isError ? '#ffebee' : '#e8f5e9'
                            })
                            .html(
                                '<strong>ID ' + postId + ':</strong> ' +
                                '<span style="color: ' + (isError ? '#dc3232' : '#46b450') + '">' +
                                (isError ? '✗ Error' : '✓ Completado') + '</span>'
                            );
                        
                        $('#batch-results').prepend(resultDiv);
                        
                        // Save progress every 3 posts
                        if ((currentIndex + 1) % 3 === 0) {
                            saveProgress();
                        }
                        
                        currentIndex++;
                        
                        if (isProcessing && currentIndex < totalPosts) {
                            setTimeout(processNextPost, TIMEOUT);
                        } else {
                            finishProcessing();
                        }
                    },
                    error: function(xhr, status, error) {
                        failedCount++;
                        
                        const resultDiv = $('<div>')
                            .css({
                                'margin': '5px 0',
                                'padding': '8px',
                                'border-left': '4px solid #dc3232',
                                'background': '#ffebee'
                            })
                            .html(
                                '<strong>ID ' + postId + ':</strong> ' +
                                '<span style="color: #dc3232">✗ Error AJAX</span><br>' +
                                '<small>' + error + '</small>'
                            );
                        
                        $('#batch-results').prepend(resultDiv);
                        
                        currentIndex++;
                        
                        if (isProcessing && currentIndex < totalPosts) {
                            setTimeout(processNextPost, TIMEOUT);
                        } else {
                            finishProcessing();
                        }
                    }
                });
            }
            
            // Finish processing
            function finishProcessing() {
                isProcessing = false;
                
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                
                saveProgress();
                
                const totalProcessed = successfulCount + failedCount;
                const message = '¡Proceso completado! ' + 
                            successfulCount + ' éxitos, ' + 
                            failedCount + ' errores.';
                
                $('#progress-status').html('<strong>' + message + '</strong>');
                if(totalPosts === totalProcessed){
                    $('#start-batch-process').hide();
                }
                $('#stop-batch-process').hide();
                $('#reset-batch-process').show();
                $('#completed-message').show();
                
                $('#batch-results').prepend(
                    '<div class="notice notice-' + (failedCount === 0 ? 'success' : (successfulCount === 0 ? 'error' : 'warning')) + '">' +
                    '<p><strong>' + message + '</strong></p>' +
                    '</div>'
                );
            }
        });
        </script>
        <?php
    }
}