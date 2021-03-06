/*

	InlineSourceEditor Plugin - by Thomas Kjoernes <thomas@ipv.no>

	Adds a button for editing HTML source inside the normal editor
	area instead of the default popup window.

*/

(function() {

	if (!tinymce) return
	if (!tinymce.ControlManager) return;

	tinymce.ControlManager.prototype.each = function(fn) {
		if (!fn) return;
		var c = null;
		var cm = this;
		for (var i in this.controls) {
			if (i) {
				var c = this.controls[i];
				var s = i.indexOf(this.prefix) !== -1 ? i.substring(this.prefix.length) : c;
				fn.call(c, s, cm);
			}
		}
	};

	var controls = {};

	tinymce.create("tinymce.plugins.InlineSourceEditor", {

		init : function(editor, url) {

			editor.addButton("inlinesourceeditor", {
				title: "advanced.code_desc",
				image: url + "/inlinesourceeditor.gif",
				cmd: "mceInlineSourceEditor"
			});

			editor.addCommand("mceInlineSourceEditor", function() {

				if (editor.__ise_visisble) {

					editor.__ise_visisble = false;
					editor.__ise_iframe.style.display = "";
					editor.__ise_textarea.style.display = "none";

					editor.setContent(editor.__ise_textarea.value);
					editor.controlManager.each(function(id) {
						if (id === "inlinesourceeditor") {
							this.setActive(false);
						} else {
							this.setDisabled(controls[id].disabled);
						}
					});

				} else {

					editor.__ise_visisble = true;
					editor.__ise_iframe.style.display = "none";
					editor.__ise_textarea.style.display = "";
					editor.__ise_textarea.value = editor.getContent();

					editor.controlManager.each(function(id) {
						if (id === "inlinesourceeditor") {
							this.setActive(true);
						} else {
							controls[id] = { disabled: this.isDisabled() };
							this.setDisabled(true);
						}
					});

				}

			});

			editor.onPostRender.add(function(editor, e) {

				var area = editor.getContentAreaContainer();
				var iframe = area.getElementsByTagName("iframe")[0];
				var textarea = document.createElement("textarea");

				textarea.value = editor.getElement().value;
				textarea.spellcheck = false;
				textarea.className = "webui-ignore";
				textarea.style.whiteSpace = "pre"; /* IE required  */
				textarea.style.background = "white";
				textarea.style.width = iframe.style.width;
				textarea.style.height = iframe.style.height;
				textarea.style.border = "none";
				textarea.style.display = "none";

				area.appendChild(textarea);

				editor.__ise_iframe = iframe;
				editor.__ise_textarea = textarea;
				editor.__ise_visible = false;

			});

		}

	});

	tinymce.PluginManager.add("inlinesourceeditor", tinymce.plugins.InlineSourceEditor);

})();