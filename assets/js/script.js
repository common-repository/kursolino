jQuery(document).ready(function () {
    var module = jQuery('#kursolino_iframe_module'),
        meta_box = jQuery('#kursolino_meta_box'),
        shortcode_wrapper = jQuery('#kursolino_shortcode');

    if (module.length) {

        jQuery('textarea').val('TEEEEEEEEEEEEEEST!');

        // load modules from api
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajaxurl,
            data: {
                action: 'kursolino_ajax',
                method: 'get-modules'
            },
            success: function (modules) {
                jQuery.each(modules, function (value, text) {
                    module.append('<option value="' + value + '">' + text + '</option>');
                });
            }
        });

        // load module options
        module.change(function () {
            var value = jQuery(this).val();
            meta_box.find('.dynamic').remove();

            if (value.length) {
                shortcode_wrapper.hide();
                meta_box.append('<span class="dashicons dashicons-update spin dynamic"></span>');

                jQuery.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: ajaxurl,
                    data: {
                        action: 'kursolino_ajax',
                        method: 'get-module-options',
                        module: value
                    },
                    success: function (modules) {
                        meta_box.find('.dynamic').remove();
                        var form = '';

                        jQuery.each(modules, function (key, module) {
                            if (module && module.hasOwnProperty('options')) {
                                var form_options = '';
                                key = (key === 'receiver') ? key : 'id';

                                var multiple = module.type === 'optgroup' || (module.hasOwnProperty('multiple') && module.multiple);

                                switch (module.type) {
                                    case 'optgroup':
                                        jQuery.each(module.options, function (label, opts) {
                                            form_options += '<optgroup label="' + label + '">';

                                            jQuery.each(opts, function (index, o) {
                                                form_options += '<option value="' + o.key + '">' + o.value + '</option>';
                                            });

                                            form_options += '</optgroup>';
                                        });

                                        form += '' +
                                            '<p class="meta-options kursolino_meta_field dynamic">' +
                                            '   <label for="' + key + '">' + module.label + '</label>' +
                                            '    <select name="' + key + '[]" multiple="multiple">' +
                                            form_options +
                                            '    </select>' +
                                            '</p>';
                                        break;
                                    case 'select':
                                        jQuery.each(module.options, function (i, o) {
                                            var v = module.hasOwnProperty('submodule') ? (o.key === 1 ? module.submodule : 'index') : o.key;

                                            if (module.hasOwnProperty('submodule') && module.submodule === 'show' && o.key > 0) {
                                                v = '|' + o.key;
                                            }

                                            form_options += '<option value="' + v + '">' + o.value + '</option>';
                                        });

                                        form += '' +
                                            '<p class="meta-options kursolino_meta_field dynamic">' +
                                            '   <label for="' + key + '">' + module.label + '</label>' +
                                            '    <select name="' + key + '"' + (multiple ? ' multiple="multiple"' : '') + '>' +
                                            form_options +
                                            '    </select>' +
                                            '</p>';
                                        break;
                                }
                            }
                        });

                        if (form.length) {
                            meta_box.append('<div class="meta-options kursolino_meta_field separator dynamic"><hr /></div>' + form);
                        }
                        meta_box.append('<div class="meta-options kursolino_meta_field separator dynamic"><hr /></div>');

                        // re-generate shortcode
                        meta_box.find('.dynamic select').change(function() {
                            kursolino_generate_shortcode();
                        });
                        kursolino_generate_shortcode();
                    }
                });
            } else {
                shortcode_wrapper.hide();
            }
        });

        // generate shortcode
        shortcode_wrapper.find('input').focus(function() {
            jQuery(this).select();
        });

        function kursolino_generate_shortcode() {
            var shortcode = 'kursolino',
                ia = '',
            s = 0;

            jQuery('#kursolino-generator').find('select').each(function () {
                var sel = jQuery(this),
                    value = jQuery(this).val();
                if (value && value.length) {
                    if (typeof value === 'object') {
                        var ids = [];
                        jQuery.each(value, function (i, o) {
                            var data = o.split('|');
                            if (data.length === 2) {
                                ids.push(data[1]);
                            } else if (sel.attr('name') === 'id[]') {
                                ids.push(o);
                            } else {
                                s++;
                                shortcode += ' ' + sel.attr('name').replace('[]', '') + '--' + s + '="' + o + '"';
                            }
                        });

                        if (ids.length) {
                            ia = 'show';
                            shortcode += ' id="' + ids.join(',') + '"';
                        }
                    } else {
                        s++;
                        var data = value.split('|');
                        if (data.length === 2) {
                            ia = 'show';
                            shortcode += ' ' + sel.attr('name') + '="' + data[1] + '"';
                        } else {
                            shortcode += ' ' + sel.attr('name') + '="' + value + '"';
                        }
                    }
                }

                if (ia.length) {
                    shortcode += ' ia="' + ia + '"';
                }
            });

            shortcode_wrapper.find('input').val('[' + shortcode + ']');
            shortcode_wrapper.css('display', 'grid');
        }
    }
});