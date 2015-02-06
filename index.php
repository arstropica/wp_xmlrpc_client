<?php
    /*ini_set('display_errors', 1);
    error_reporting(E_ALL);*/

    require_once('functions.php');
    require_once('class-IXR.php');

    $content = "";
    $post_title= "";
    $post_categories="";
    $post_tags="";
    $post_blogs = array();
    $blog_host = "";
    $blog_user = "";
    $blog_pass = "";

    if ($_POST):
        $content = $_POST['contentEditor'];
        $post_title = $_POST['post_title'];
        $post_categories = trim($_POST['postcats'], " ,");
        $post_tags = trim($_POST['posttags']," ,");
        $post_blogs = $_POST['selwpblogs'];
        $blog_host = $_POST['blog_host'];
        $blog_user = $_POST['blog_user'];
        $blog_pass = $_POST['blog_pass'];
        
        // Set Blog URL and Credentials (Multiple Blogs can be specified here)
        $creds = array(
            0 => array(
                'host' => $blog_host,
                'user' => $blog_user,
                'pass' => $blog_pass
            )
        );

        $success_post = false;
        $output = false;

        if (!empty($post_title) && !empty($content) && !empty($creds)){
            $success_post = true;
            foreach($creds as $blog_creds){
                $host = xmlrpc_host_script($blog_creds['host']);
                $user = $blog_creds['user'];
                $pass = $blog_creds['pass'];

                $success_post = xmlrpc_publish($host, $user, $pass, $post_title, $content, $output, $post_tags, $post_categories) ? $success_post : false;
            }
        }

        if ($success_post):
        ?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
            <head>
                <title>XML-RPC Post Editor</title>
                <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
                <link rel="stylesheet" href="css/custom.css" type="text/css" />
            </head>
            <body>
                <div class="wrap">
                    <br /><br /><br />
                    <p style="text-align: center;"><strong>Success! Your Post: <?php echo $post_title; ?> has been published.</strong></p>
                    <p style="text-align: center; margin-top: 10px;"><button class="button-primary" onClick="parent.$.fancybox.close();">Close</button></p>
                </div>
            </body>
        </html>
        <?php exit;
            else:
            $errormsg = empty($output) ? "Sorry, something went wrong. A required field may not have been completed. Your post was not published." : "Sorry, the following error occurred: " . $output;
        ?>
        <script type="text/javascript">
            alert('<?php echo $errormsg; ?>');
        </script>
        <?php
            endif;
        endif ;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
    <head>
        <title>XML-RPC Post Editor</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <meta http-equiv="no-cache">
        <meta http-equiv="Expires" content="-1">
        <meta http-equiv="Cache-Control" content="no-cache">
        <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
        <script src="js/jquery.cookie.js" type="text/javascript"></script>
        <script type="text/javascript" src="js/tiny_mce/tiny_mce.js"></script>
        <script type="text/javascript" src="js/fancybox/jquery.fancybox.pack.js?v=2.0.6"></script>
        <script type="text/javascript" src="js/validation.js"></script>
        <script src="js/jquery-ui-1.8.22.custom.min.js" type="text/javascript"></script>
        
        <link rel="stylesheet" type="text/css" href="js/fancybox/jquery.fancybox.css?v=2.0.6" media="screen" />
        <link rel="stylesheet" href="css/custom.css" type="text/css" />
        <link rel="stylesheet" type="text/css" href="css/ui-lightness/jquery-ui-1.8.22.custom.css">

        <script type="text/javascript">
            tinyMCE.init({
                oninit : tinyOnInit,
                setup : tinySetUp,
                extended_valid_elements : "iframe[src|width|height|name|align|frameborder|scrolling]",
                skin: "cirkuit",
                mode : "textareas",
                theme : "advanced",
                plugins: "pdw,spellchecker,safari,pagebreak,style,layer,table,save,advimage,advlink,advlist,emotions,iespell,inlinepopups,insertdatetime,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,inlinesourceeditor,tabfocus",
                // width: "640",
                // height: "480",
                tab_focus : ':prev,:next',
                // Theme options

                theme_advanced_buttons1 : "formatselect,fontsizeselect,forecolor,|,bold,italic,strikethrough,|,bullist,numlist,|,justifyleft,justifycenter,justifyright,|,link,unlink,|,spellchecker,|,image,|,pdw_toggle",
                theme_advanced_buttons2 : "inlinesourceeditor,paste,pastetext,pasteword,removeformat,|,backcolor,|,underline,justifyfull,sup,|,outdent,indent,|,hr,anchor,charmap,|,media,|,search,replace,|,fullscreen,|,undo,redo",
                theme_advanced_buttons3 : "tablecontrols,|,visualaid",

                pdw_toggle_on : !$.cookie('mcePDWToggleToolbars'),
                pdw_toggle_toolbars : "2,3",

                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "left",
                theme_advanced_statusbar_location : "bottom",
                theme_advanced_resizing : false,
                convert_urls : false
            });

            function tinyOnInit(){
                // cookie expiration date
                var cookieExpireDate = new Date();
                var tinyMCEInstances = $('.tinyMCE');
                cookieExpireDate.setTime(cookieExpireDate.getTime() + (365 * 3 * 24 * 60 * 60 * 1000));
                tinymce.dom.Event.add("wpPrimaryEd_pdw_toggle", 'click', function(e) {
                    var advButtonsVisible = !$.cookie('mcePDWToggleToolbars');
                    if (!advButtonsVisible) {
                        $.cookie('mcePDWToggleToolbars',null,{path:'/',expires:cookieExpireDate});
                    }
                    else { 
                        $.cookie('mcePDWToggleToolbars',1,{path:'/',expires:cookieExpireDate});
                    }
                });

                var last_state = ((typeof $.cookie('mceVisualToggle') != 'undefined') ? parseInt($.cookie('mceVisualToggle')) : 0) || 0;
                var visualState = (last_state == 0);
                if (visualState){
                    $('#wpPrimaryEdTabs A#visual_tab').addClass('active');
                } else {
                    tinyMCE.activeEditor.execCommand('mceInlineSourceEditor');
                    $('#wpPrimaryEdTabs A#source_tab').addClass('active');
                }

                var tinyMCEVisualToggle = $('#wpPrimaryEdTabs A').click(function(e){
                    e.preventDefault();
                    var last_state = ((typeof $.cookie('mceVisualToggle') != 'undefined') ? parseInt($.cookie('mceVisualToggle')) : 0) || 0;
                    var new_state = $(this).attr('id') == 'visual_tab' ? 0 : 1;
                    var toggleState = (last_state + new_state) == 1;
                    var currentEditorId = tinyMCE.activeEditor.id;
                    var currentEditor = tinyMCE.activeEditor; 
                    currentEditor.focus();
                    if (toggleState) {
                        currentEditor.execCommand('mceInlineSourceEditor');
                        $('#wpPrimaryEdTabs A').addClass('active').not(this).removeClass('active');
                        if ($(this).attr('id') == 'visual_tab'){
                            $.cookie('mceVisualToggle',0,{path:'/',expires:cookieExpireDate});
                        }
                        else { 
                            $.cookie('mceVisualToggle',1,{path:'/',expires:cookieExpireDate});
                        }
                    }
                    return false;                    
                });
            }

            function tinySetUp(ed){
                ed.onInit.add(function (ed, e) {
                    // Fix for fancybox scrollbars
                    $(ed.getDoc()).children().find('head').append('<style type="text/css">html { overflow-x:hidden;overflow-y:scroll; }</style>');
                })
            }

        </script>

        <script type="text/javascript">
            var posttags = "";
            var postcats = "";

            function tiny_insert_img_at_cursor(filename){
                var ed = tinyMCE.get('wpPrimaryEd');                // get editor instance
                var range = ed.selection.getRng();                  // get range
                var newNode = ed.getDoc().createElement ( "img" );  // create img node
                newNode.src=filename;                               // add src attribute
                range.insertNode(newNode);                          // insert Node
            }

            var handleFBClose = function(fbTitle) {
                if (fbTitle == "upload"){
                    var img_url_split = $.cookie('blog_plupload_imgs');
                    if (typeof img_url_split != 'undefined' && img_url_split != null){
                        var img_url_arry = img_url_split.split(/,/);
                        // console.log(img_url_arry);
                        for(var i=0; i < img_url_arry.length; i++){
                            tiny_insert_img_at_cursor(img_url_arry[i]);
                            // console.log(img_url_arry[i]);
                        }
                    }
                }
                if (fbTitle == "tag"){
                    $("#posttags_display").val(posttags);
                    $("#posttags").val(posttags);
                }
                if (fbTitle == "cat"){
                    $("#postcats_display").val(postcats);
                    $("#postcats").val(postcats);
                }
                $.fancybox.close();
                return false;
            }
        </script>

        <script type="text/javascript">
            $(function(){
                // Post Title Overlay
                var toggleTitle = function(){
                    if($("#titlewrap #title").val() == ""){
                        $("#titlewrap #title-prompt-text").css({'visibility': 'visible', 'z-index' : 10});
                    } else {
                        $("#titlewrap #title-prompt-text").css({'visibility': 'hidden', 'z-index' : -10});
                    }
                };
                var hideLabel = function(){
                    $("#titlewrap #title-prompt-text").css({'visibility': 'hidden', 'z-index' : -10});
                };

                toggleTitle();
                $("#titlewrap #title").bind('change, blur', toggleTitle);
                $("#titlewrap #title").bind('click, focus', hideLabel);

            });
        </script>

        <script type="text/javascript">
            $(function(){
                $(".fancybox.ajax").fancybox({
                    fitToView       : false,
                    width           : 600,
                    height          : 400,
                    autoSize        : false,
                    closeBtn        : false,
                    closeClick      : false,
                    openEffect      : 'none',
                    closeEffect     : 'none',
                    type            : 'ajax',
                    afterShow       : function() {
                        $(".fancybox-title-inside-wrap").css({'background':'#777', 'color':'#FFF', 'padding':'3px'});
                    },
                    helpers:  {
                        title : {
                            type : 'inside'
                        },
                        overlay : {
                            css : {
                                'background-color' : '#fff'
                            }
                        }
                    },
                    ajax: {
                        type: 'POST',
                        // Blog Credentials for Taxonomy
                        data: 'host=' + $("#blog_host").val() + '&user=' + $("#blog_user").val() + '&pass=' + $("#blog_pass").val(),
                        success: function(){
                            return false;
                        }
                    }
                });        
                $(".fancybox.iframe").fancybox({
                    fitToView       : false,
                    width           : 600,
                    height          : 400,
                    autoSize        : false,
                    closeBtn        : false,
                    closeClick      : false,
                    openEffect      : 'none',
                    closeEffect     : 'none',
                    type            : 'iframe',
                    afterShow       : function() {
                        $(".fancybox-title-inside-wrap").css({'background':'#777', 'color':'#FFF', 'padding':'3px'});
                    },
                    helpers:  {
                        title : {
                            type : 'inside'
                        },
                        overlay : {
                            css : {
                                'background-color' : '#fff'
                            }
                        }
                    }
                });        
            });        
        </script>

    </head>
    <body>
        <div class="wrap">
            <form method="post" name="xmlrpcPost" id="xmlrpcPost">
                <p id="error">Please make sure all required fields are completed.</p>
                <div id="header">
                    <div id="icon_container">
                        <div id="icon-edit" class="icon32 icon32-posts-post"><br></div>
                        <h2 id="pagetitle">Add New Post</h2>
                    </div>
                    <div id="blog_credentials">
                        <fieldset>
                            <div>
                                <label>Blog URL:</label>
                                <input type="text" name="blog_host" id="blog_host" value="<?php echo $blog_host; ?>" />
                            </div>
                            <div>
                                <label>Username:</label>
                                <input type="text" name="blog_user" id="blog_user" value="<?php echo $blog_user; ?>" />
                            </div>
                            <div>
                                <label>Password:</label>
                                <input type="password" name="blog_pass" id="blog_pass" value="<?php echo $blog_pass; ?>" />
                            </div>
                        </fieldset>
                    </div>
                </div>
                <br class="clearboth" />
                <div id="titlediv">
                    <div id="titlewrap">
                        <label class="hide-if-no-js" style="visibility: hidden; z-index: -10;" id="title-prompt-text" for="title">Enter title here</label>
                        <input type="text" name="post_title" tabindex="1" value="<?php echo $post_title; ?>" id="title">
                    </div>
                </div>

                <div id="edToolbar">
                    <div id="wpPrimaryEdTabs" class="tabs">
                        <a id="source_tab" href="#"><span class="code">HTML</span></a>
                        <a id="visual_tab" href="#"><span class="wysiwyg">Visual</span></a>
                        <br style="clear:both" />
                    </div>
                    <div id="media-buttons">
                        <a id="upload_media" title="Upload/Insert Image(s)" href="upload.php" class="fancybox iframe">Upload/Insert <img src="images/media-button.png" width="15" height="15"></a>
                    </div>
                </div>
                <textarea name="contentEditor" class="tinyMCE" id="wpPrimaryEd" style="width:100%; min-height: 300px;"><?php echo $content; ?></textarea>
                <br style="clear: both; width: 100%; height: 0px; line-height: 0px;"/>
                <div id="post-meta-container">
                    <h3>Post Details</h3>
                    <div class="inside">
                        <h4>Post Tags <span>Select / Edit Tags: </span></h4>
                        <p><a id="showTags" title="Select Tags" href="taxonomy.php?tax=tag" class="fancybox taxon ajax"><input type="text" id="posttags_display" style="width:95%;" readonly="readonly" value="<?php echo $post_tags; ?>" /></a></p><input type="hidden" id="posttags" name="posttags" value="<?php echo $post_tags; ?>" />
                    </div>
                    <div class="inside">
                        <h4>Post Categories <span>Select Categories: </span></h4>
                        <p><a id="showCats" title="Select Categories" href="taxonomy.php?tax=cat" class="fancybox taxon ajax"><input type="text" id="postcats_display" style="width:95%;" readonly="readonly" value="<?php echo $post_categories; ?>" /></a></p><input type="hidden" id="postcats" name="postcats" value="<?php echo $post_categories; ?>" />
                    </div>
                </div>
                <div class="submit_btn_div">
                    <p><input type="submit" id="tinyPubBtn" class="button-primary" value="Publish" /></p>
                </div>
            </form>
        </div>
    </body>
    </html>