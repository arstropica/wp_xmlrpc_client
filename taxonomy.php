<?php
    // Taxonomy
    require_once('functions.php');

    if(empty($_GET['tax'])){
        echo "<p>Taxonomy not specified.</p>";
        return;
    }
    if(empty($_POST['host']) || empty($_POST['host']) || empty($_POST['host'])){
        echo "<p>Blog Credentials not specified.</p>";
        return;
    }
    $tax_type = $_GET['tax'];
    $host = xmlrpc_host_script($_POST['host']);
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    switch($tax_type){
        case 'tag':
            $tax_request = 'wp.getTags';
            $para_id = 'tagcloud-post_tag';
            $para_class = 'the-tagcloud';
            $anchor_class = 'tag-link';
            $returnIDKey = 'tag_id';
            $returnNameKey = 'name';
            $jvariable = 'posttags';
            $input_id = 'newtag';
            $tax_name = 'Tags';
            break;
        case 'cat':
            $tax_request = 'wp.getCategories';
            $para_id = 'catlist-post_cat';
            $para_class = 'the-catlist';
            $anchor_class = 'cat-link';
            $returnIDKey = 'categoryId';
            $returnNameKey = 'categoryName';
            $jvariable = 'postcats';
            $input_id = 'newcat';
            $tax_name = 'Categories';
            break;
    }


    $rpcTaxArry = array();

    xmlrpc_request(
    $host,
    $user,
    $pass,
    $tax_request,
    0,
    array(),
    $rpcTaxArry
    );
    //var_dump($rpcTaxArry);

    $taxList = array();
    $taxListHTML = "<p id=\"$para_id\" class=\"$para_class\">\n";
    if (!empty($rpcTaxArry)){
        foreach($rpcTaxArry[0] as $taxArry){
            $taxListHTML .="<a href=\"#\" class=\"$anchor_class-" . $taxArry[$returnIDKey] . "\" title=\"" . $taxArry[$returnNameKey] . " Posts\" style=\"font-size: " . (($tax_type == 'tag') ? (8 + min(intval($taxArry['count']), 20)) : "12") . "pt;\">" . $taxArry[$returnNameKey] . "</a>\n";
            $taxList[] = $taxArry[$returnNameKey];
        }
    }
    $taxListHTML .= "</p>\n";
?>
<script type="text/javascript">
    $(function(){
        $('.<?php echo $para_class; ?> A').click(function(e){
            e.preventDefault();
            var taxName = $(this).text();
            var current_value = $("#<?php echo $input_id; ?>").val();
            $("#<?php echo $input_id; ?>").val(current_value + ((current_value.lastIndexOf(",") >= ((current_value.length - 2))) ? "" : (", ")) + taxName + ", ");
            $("#<?php echo $input_id; ?>").trigger("change");
            return false;
        });
        $(".<?php echo $tax_type; ?>checklist").hide();
        $("#existing_<?php echo $tax_type; ?>s").click(function(e){
            e.preventDefault();
            $(".<?php echo $tax_type; ?>checklist").toggle();
            return false;
        });
        <?php echo "var availableTerms = [\"" . implode('","', $taxList) . "\"];\n"; ?>

        function split( val ) {
            return val.split( /,\s*/ );
        }

        function extractLast( term ) {
            return split( term ).pop();
        }

        $( "#<?php echo $input_id; ?>" ).val(<?php echo $jvariable; ?>)
        .bind("keydown", function(event){
            if(event.keyCode === $.ui.keyCode.TAB && $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })                                
        .autocomplete({
            minLength: 2,
            // source: availableTerms,
            source: function (request, response) {
                // delegate back to autocomplete, but extract the last term
                response( $.ui.autocomplete.filter(
                availableTerms, extractLast( request.term ) 
                ));
            },
            focus: function() {
                // prevent value inserted on focus when navigating the drop down list
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( ui.item.value );
                // add placeholder to get the comma-and-space at the end
                terms.push( "" );
                this.value = terms.join( ", " );
                <?php echo $jvariable; ?> = terms.join( ", " );
                return false;
            }
        })
        .change(function(){
            <?php echo $jvariable; ?> = $(this).val();
        });
    });
</script>
<div class="wrap">
    <h2>Select <?php echo ucwords($tax_name); ?></h2>
    <p style="font-size: 10pt; font-style: italic;">Select or enter a comma-separated list of <?php echo strtolower($tax_name); ?>.</p>
    <form name="<?php echo $tax_type; ?>s_select">
        <p><input type="text" id="<?php echo $input_id; ?>" name="<?php echo $input_id; ?>" style="width:100%;" /></p>
        <div class="existing_terms_container">
            <div>
                <div class="fl">
                    <a href="#" id="existing_<?php echo $tax_type; ?>s">Choose from existing <?php echo strtolower($tax_name); ?></a>
                </div>
                <div class="fr">
                    <button class="button-primary" onclick="handleFBClose('<?php echo $tax_type; ?>'); return false;">Update <?php echo ucwords($tax_name); ?></button>
                </div>
            </div>
            <br class="clearboth" />
            <div class="<?php echo $tax_type; ?>checklist">
                <?php echo $taxListHTML; ?>
            </div>
        </div>
    </form>
</div>