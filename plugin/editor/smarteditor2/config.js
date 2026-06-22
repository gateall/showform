(function($){
    $(document).ready(function() {
        console.log('smarteditor2 config.js loaded');
        console.log('g5_editor_url =', typeof g5_editor_url !== 'undefined' ? g5_editor_url : 'undefined');
        console.log('oEditors =', typeof oEditors !== 'undefined' ? 'loaded' : 'undefined');
        console.log('nhn =', typeof nhn !== 'undefined' ? 'loaded' : 'undefined');

        $(".smarteditor2").each(function(index){
            var get_id = $(this).attr("id");

            console.log('editor target:', get_id, 'nodeName:', $(this).prop("nodeName"));

            if (!get_id || $(this).prop("nodeName") != 'TEXTAREA') return true;

            if (typeof nhn === 'undefined' || !nhn.husky || !nhn.husky.EZCreator) {
                console.log('EZCreator not loaded');
                return true;
            }

            if (typeof oEditors === 'undefined') {
                console.log('oEditors not defined');
                return true;
            }

            if (typeof g5_editor_url === 'undefined') {
                console.log('g5_editor_url not defined');
                return true;
            }

            nhn.husky.EZCreator.createInIFrame({
                oAppRef: oEditors,
                elPlaceHolder: get_id,
                sSkinURI: g5_editor_url + "/SmartEditor2Skin.html",
                htParams: {
                    bUseToolbar: true,
                    bUseVerticalResizer: true,
                    bUseModeChanger: true,
                    bSkipXssFilter: true,
                    fOnBeforeUnload: function(){}
                },
                fOnAppLoad: function(){
                    console.log('SmartEditor loaded:', get_id);
                },
                fCreator: "createSEditor2"
            });
        });

        if ($(".smarteditor2").length === 0) {
            console.log('.smarteditor2 textarea not found');
        }
    });
})(jQuery);