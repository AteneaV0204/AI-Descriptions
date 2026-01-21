<?php

class InclusiveAiDescriptions {

    //Variables
    private static $instance;
    private static $post_type = 'fotografia';
    private static $apiKey = 'buy one urself';
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
        $imgData = wp_get_attachment_image_src($imagen, 'full');
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
            echo $this->GPTRequest($url, $key, $body);

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
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
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
            <p>Página en construcción...</p>
            <h2>Estadísticas</h2>
                
            <?php
            // Obtener todas las fotografías
            $all_args = array(
                'post_type' => 'fotografia',
                'posts_per_page' => -1,
                'post_status' => array('publish', 'pending', 'draft')
            );
            $all_posts = get_posts($all_args);
                
            //Empty description posts
            $empty_args = array(
                'post_type' => 'fotografia',
                'posts_per_page' => -1,
                'post_status' => array('publish', 'pending', 'draft'),
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
                
            // Obtener fotografías SIN imagen destacada
            $no_featured = array();
            foreach($empty_posts as $post) {
                if(!has_post_thumbnail($post->ID)) {
                    $no_featured[] = $post;
                }
            }
                
            $total_all = count($all_posts);
            $total_empty = count($empty_posts);
            $total_with_featured = $total_empty - count($no_featured);
            ?>

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
                </tbody>
            </table>
            <?php if($total_with_featured > 0): ?>
                <div id="batch-process-container" style="margin-top: 20px;">
                    <h3>Procesamiento en Lote</h3>
                    <p>Se generarán descripciones IA para <?php echo $total_with_featured; ?> fotografías.</p>
                    
                    <button type="button" id="start-batch-process" class="button button-primary button-large">
                        Iniciar Procesamiento en Lote
                    </button>
                    
                    <button type="button" id="stop-batch-process" class="button button-secondary" style="display: none;">
                        Detener Procesamiento
                    </button>
                    
                    <div id="batch-progress" style="margin-top: 20px; display: none;">
                        <div class="progress-bar" style="width: 100%; background-color: #f1f1f1; border-radius: 3px;">
                            <div id="progress-bar-inner" style="width: 0%; height: 20px; background-color: #0073aa; border-radius: 3px; transition: width 0.3s;"></div>
                        </div>
                        <p id="progress-text">Procesando: 0/<?php echo $total_with_featured; ?></p>
                        <p id="progress-status">Preparando...</p>
                        <div id="batch-results" style="margin-top: 20px;"></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="notice notice-success">
                    <p>¡Todas las fotografías ya tienen descripción IA o no tienen imagen destacada!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let processing = false;
            let currentIndex = 0;
            let postsToProcess = <?php echo json_encode(array_map(function($p) { return $p->ID; }, array_filter($empty_posts, function($p) { return has_post_thumbnail($p->ID); }))); ?>;
            
            $('#start-batch-process').on('click', function() {
                if(postsToProcess.length === 0) {
                    alert('No hay fotografías para procesar');
                    return;
                }
                
                processing = true;
                currentIndex = 0;
                
                $('#batch-progress').show();
                $('#start-batch-process').hide();
                $('#stop-batch-process').show();
                $('#batch-results').html('<p>Iniciando...</p>');
                
                processNextPost();
            });
            
            $('#stop-batch-process').on('click', function() {
                processing = false;
                $('#progress-status').text('Proceso detenido por el usuario');
                $('#start-batch-process').show();
                $('#stop-batch-process').hide();
            });
            
            function processNextPost() {
                if(!processing || currentIndex >= postsToProcess.length) {
                    finishProcessing();
                    return;
                }
                
                const postId = postsToProcess[currentIndex];
                currentIndex++;
                
                const progressPercent = (currentIndex / postsToProcess.length) * 100;
                $('#progress-bar-inner').css('width', progressPercent + '%');
                $('#progress-text').text('Procesando: ' + currentIndex + '/' + postsToProcess.length);
                $('#progress-status').text('Generando descripción para ID: ' + postId);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ai-gen-description-batch',
                        post_id: postId
                    },
                    success: function(response) {
                        $('#batch-results').prepend(
                            '<div style="margin: 5px 0; padding: 5px; background: #f1f1f1;">ID ' + postId + ': ' + 
                            (response.success ? '✓ Completado' : '✗ Error') + '</div>'
                        );
                        
                        const delay = 1500;
                        setTimeout(processNextPost, delay);
                    }
                });
            }
            
            function finishProcessing() {
                processing = false;
                $('#progress-status').html('<strong>¡Proceso completado!</strong>');
                $('#start-batch-process').show().text('Reiniciar Procesamiento');
                $('#stop-batch-process').hide();
            }
        });
        </script>
        <?php
    }

}