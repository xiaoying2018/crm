/*global $, Notification*/

// (function () {

    'use strict';

    var position = 2;

    function animateMessage(thumb_path, full_name, content){
        Notification.create(
            // Title
            full_name,
            // Text
            // "Long text Long text Long text Long text. 2 lines = Perfect ;)",
            content,
            // Illustration
            thumb_path,
            // Effect
            'fadeInDown',
            // Position
            position
        );
    }

    // $('.notify').click(function (event) {
    //     Notification.create(
    //         // Title
    //         "Notification title",
    //         // Text
    //         "Long text Long text Long text Long text. 2 lines = Perfect ;)",
    //         // Illustration
    //         "img/user.jpg",
    //         // Effect
    //         $(event.target).text(),
    //         // Position
    //         position
    //     );
    // });

    $(document).on('click','.dismiss',function(){
        $(document).find('.notification-' + $(this).attr('rel')).remove();
    });

// }());
