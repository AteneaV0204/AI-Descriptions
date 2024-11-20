jQuery(document).ready(function($) {
    $(document).on("click", "#ai_description_button", function () {
        var button = $(this);
        var description = $("textarea[name='acf[field_6731f5a2ecd8f]']").val();

        const postImageDiv = $('#postimagediv');
        const images = postImageDiv.find('img');
        const imageUrls = [];

        images.each(function() {
            imageUrls.push($(this).attr('src'));
        });

        $('#saving-description').css('display', 'inline-block');
        button.hide();

        //AJAX for the description
        var params = {
            'action': 'ai-gen-description',
            'og': description,
            'post_id': postID,
            'img_url': imageUrl.imgUrl
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