jQuery(document).ready(function($) {
    $(document).on("click", "#ai_description_button", function () {
        var button = $(this);
        var description = $("textarea[name='acf[field_6731f5a2ecd8f]']").val();

        $('#saving-description').css('display', 'inline-block');
        button.hide();

        var postId = $('#post_ID').val();

        //AJAX for the description
        var params = {
            'action': 'ai-gen-description',
            'og': description,
            'post_id': postId
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