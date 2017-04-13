$(function(){
   $('form').on('submit', function(e){
       var url = $(this).find('input[name="url"]').val();
       var type = $(this).find('input[name="result-type"]:checked').val();

       /*if (!url || !validateURL(url)) {
           alert('Format for URL - http://www.domain.com/ or http://domain.com/');

           return false;
       }*/

       /*if (url && validateURL(url) && type) {
           $loader = $('#loader');
           $loader.show();

           $.post($(this).attr('action'), $(this).serialize(), function(data){
               $loader.hide();

               $('#download-file').attr({
                   'href': location.href + JSON.parse(data),
                   'target': '_blank'
               }).removeClass('disabled');
           });
       }*/
   });

   function validateURL(textval) {
       var urlregex = /^(https?|ftp):\/\/([a-zA-Z0-9.-]+(:[a-zA-Z0-9.&%$-]+)*@)*((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}|([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(:[0-9]+)*(\/($|[a-zA-Z0-9.,?'\\+&%$#=~_-]+))*$/;
       return urlregex.test(textval);
   }
});

window.downloadUrlsHtml = function (domain, id) {
    $(function(){
        $loader = $('#loader');
        $loader.show();

        $.post('functions/process_html.php', {'domain': domain, 'id': id}, function(data){
            $loader.hide();

            window.open(
                location.href + JSON.parse(data),
                '_blank'
            );
        });
    });
};
