jQuery(document).ready(function($) {
    $(document).on("click", "#ai_description_button", function () {
        var button = $(this);
        var description = $("textarea[name='acf[field_6731f5a2ecd8f]']").attr('src');

        $('#saving-description').css('display', 'inline-block');
        button.hide();

        // Petición AJAX para la generación del título resumido
        var params = {
            'action': 'ai-gen-description',
            'og': description,
            'img_url': imgUrl
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: params,
            dataType: 'html',
            success: function (res) {
                var textarea = $("textarea[name='acf[field_6731f5a2ecd8f]']")
                textarea.val(res);
                $('#saving-description').hide();
                button.show();
            }
        });
    });
});