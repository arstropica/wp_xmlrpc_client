<?php
    // uploader
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>

        <title>Upload / Insert Images</title>

        <style type="text/css">
            body {
                font-family:Verdana, Geneva, sans-serif;
                font-size:13px;
                color:#333;
            }
            #upload_actions
            {
                padding: 8px;
            }
            #upload_actions A
            {
                padding: 5px 10px;
                display: inline-block;
                text-decoration: none;
                color: #FFF;
                font-size: 11pt;
                background: #333;
            }
            #upload_actions A.inactive
            {
                background: #BBB;
            }
            #upload_actions A.active
            {
                background: #00F;
            }
            #upload_actions #cancel
            {
                background: #777;
            }
            .upditem
            {
                padding: 5px;
                background: #555;
                color: white;
                margin: 5px 0;                
            }
            .upditem.err
            {
                background: #999;
                color: #f00;
            }
        </style>

        <!-- Load Queue widget CSS and jQuery -->
        <script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
        <!--<style type="text/css">@import url(/_js/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css);</style>-->

        <!-- Third party script for BrowserPlus runtime (Google Gears included in Gears runtime now) -->
        <script type="text/javascript" src="js/plupload/browserplus-min.js"></script>

        <!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->
        <script type="text/javascript" src="js/plupload/plupload.full.js"></script>
        <!--<script type="text/javascript" src="/_js/plupload/jquery.plupload.queue/jquery.plupload.queue.js"></script>-->
        <script type="text/javascript" src="js/jquery.cookie.js"></script>

        <script type="text/javascript">
            $(function(){
                $("A.inactive").click(function(e){
                    e.preventDefault();
                    return false;
                });

                // Cancel / Close Handler
                $('#upload_actions A#insert').click(function(e){
                    if($(this).hasClass('inactive')) return false;
                    e.preventDefault();
                    parent.handleFBClose('upload');
                    return false;
                });
            });
        </script>

        <script type="text/javascript">
            // Convert divs to queue widgets when the DOM is ready
            $(function() {
                $.cookie("blog_plupload_imgs", null, { path: '/' });
                var uploader = new plupload.Uploader({
                    // General settings
                    runtimes : 'gears,flash,silverlight,browserplus,html5',

                    max_file_size : '10mb',
                    url : 'upload_hndlr.php',
                    chunk_size : '1mb',
                    unique_names : true,
                    browse_button : 'pickfiles',
                    container : 'upload_actions',

                    // Resize images on clientside if we can
                    resize : {width : 600, height : 450, quality : 75},

                    // Specify what files to browse for
                    filters : [
                    {title : "Image files", extensions : "jpg,gif,png"}
                    ],

                    // Flash settings
                    flash_swf_url : 'js/plupload/plupload.flash.swf',

                    // Silverlight settings
                    silverlight_xap_url : 'js/plupload/plupload.silverlight.xap'
                });
                var total_upload_files = 0;
                //var uploader = new plupload.Uploader;
                var filenames = new Array;

                uploader.bind('Init', function(up, params) {
                    $('#filelist').html("<div>You are using the <u>" + params.runtime + "</u> runtime.</div>");
                });

                $('#uploadfiles').click(function(e) {
                    uploader.start();
                    e.preventDefault();
                });

                uploader.init();

                uploader.bind('FilesAdded', function(up, files) {
                    $.each(files, function(i, file) {
                        $('#filelist').append(
                        '<div class="upditem" id="' + file.id + '">' +
                        file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
                        '</div>');
                    });

                    up.refresh(); // Reposition Flash/Silverlight
                });

                uploader.bind('UploadProgress', function(up, file) {
                    $('#' + file.id + " b").html(file.percent + "%");
                });

                uploader.bind('Error', function(up, err) {
                    $('#filelist').append("<div class='upditem err'>Error: " + err.code +
                    ", Message: " + err.message +
                    (err.file ? ", File: " + err.file.name : "") +
                    "</div>"
                    );

                    up.refresh(); // Reposition Flash/Silverlight
                });

                uploader.bind('FileUploaded', function(up, file, response) {
                    if (total_upload_files > 0){
                        $("#insert").removeClass("inactive").addClass('active');
                    }
                    $('#' + file.id + " b").html("100%");
                    var json_response = $.parseJSON(response.response);
                    // var filename = json_response.cleanFileName;
                    var filename = json_response.fileURL;
                    // console.log(filename);
                    if (typeof filename != 'undefined') filenames.push(filename); 
                    total_upload_files--;
                    if(total_upload_files == 0){
                        // console.log(filenames);
                        var date = new Date();
                        date.setTime(date.getTime() + (10 * 60 * 1000));
                        $.cookie('blog_plupload_imgs', filenames.join(','), { expires: date, path: '/' })
                    }
                });

                uploader.bind('QueueChanged', function(up, files) {
                    total_upload_files = uploader.files.length;
                    if (total_upload_files > 0){
                        $("#uploadfiles").removeClass('inactive').addClass('active');
                    } else {
                        $("#uploadfiles").removeClass('active').addClass('inactive');
                        $("#insert").removeClass('active').addClass("inactive");
                    }
                });


                // Client side form validation
                $('form').submit(function(e) {
                    var uploader = $('#uploader').pluploadQueue();

                    // Files in queue upload them first
                    if (uploader.files.length > 0) {
                        // When all files are uploaded submit form
                        uploader.bind('StateChanged', function() {
                            if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
                                $('form')[0].submit();
                            }
                        });

                        uploader.start();
                    } else {
                        alert('You must queue at least one file.');
                    }

                    return false;
                });
            });
        </script>
    </head>
    <body>

        <form>
            <div id="uploader">
            </div>
        </form>
        <div id="upload_actions">
            <div id="filelist">Your browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</div>
            <br />
            <a id="pickfiles" class="button" href="#">Select files</a>
            <a id="uploadfiles" href="#" class="inactive">Upload files</a>
            <a href="#" id="insert" class="inactive">Insert</a>
            <a href="#" id="cancel" onClick="parent.$.fancybox.close();">Cancel</a>
        </div>
    </body>
</html>