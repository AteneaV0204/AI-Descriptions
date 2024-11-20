<?php

class InclusiveAiDescriptions {

    //Variables
    private static $instance;
    private static $post_type = 'fotografia';
    private static $apiKey = 'I AM ERROR';
    private static $model = 'gpt-4o';
    private $imgUrl;

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
        $postId = get_the_ID();
        $imgData = wp_get_attachment_image_src(get_post_thumbnail_id($postId), 'full');
        $imgUrl = $imgData ? $imgData[0] : '';

        $params = [
            'og' => isset($_POST['og']) ? $_POST['og'] : '',
            'img_url' => $imgUrl
        ];

        try {
            $url = 'https://api.openai.com/v1/chat/completions';
            $key = self::$apiKey;
            $body = '{
                    "model": "' . self::$model . '",
                    "temperature": 0.2,
                    "messages": [
                        {
                            "role": "system",
                            "content": "Eres una herramienta de descripcion de imagenes para personas ciegas. Las imagenes que recibes son de un concurso de fotografia sobre la vida con discapacidad y tu labor se corresponde a describirlas muy detalladamente para personas con problemas visuales. Si hay alguna persona con discapacidad, debes incluirlo en la descripcion y decir cual es: fisica, intelectual, auditiva, visual, paralisis cerebral, sordoceguera, problemas de lenguaje, esclerosis multiple, lesion  cerebral o parkinson. Tambien intenta distinguir si la persona tiene problemas de salud mental, autismo. Si la persona tiene Sindrome de Down no digas que tiene discapacidad intelectual. Di tambien si la imagen corresponde a un retrato y el tema que aborda como trabajo, deporte, ocio, salud, accesibilidad, salud, rehabilitacion, educacion, empleo, servicios sociales, turismo, cultura, vivienda o ayudas tecnicas. Valora sobre todo la cara para determinar el tipo de discapacidad"
                        },
                        {"role": "user", "content": [
                            {
                                "type": "text",
                                "text": "Describe la siguiente imagen"
                            },
                            {
                                "type": "image_url",
                                "image_url": {
                                    "url": "' . $this->imgUrl . '"
                                }
                           }]
                        }
                    ]}';
            echo $this->GPTRequest($url, $key, $body);

        } catch (\Exception $e) {
            echo "No se ha podido procesar la imagen. Inténtelo en unos minutos.";
        }
        exit(0);
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
            wp_localize_script('ai-js', 'imageUrl', array(
                'imgUrl' => $this->imgUrl,
            ));
        }
    }

}