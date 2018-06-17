/*global wc_enhanced_select_params, wc4bp_groups */
jQuery(function ($) {
    function getEnhancedSelectFormatString() {
        return {
            'language': {
                errorLoading: function () {
                    // Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
                    return wc_enhanced_select_params.i18n_searching;
                },
                inputTooLong: function (args) {
                    var overChars = args.input.length - args.maximum;

                    if (1 === overChars) {
                        return wc_enhanced_select_params.i18n_input_too_long_1;
                    }

                    return wc_enhanced_select_params.i18n_input_too_long_n.replace('%qty%', overChars);
                },
                inputTooShort: function (args) {
                    var remainingChars = args.minimum - args.input.length;

                    if (1 === remainingChars) {
                        return wc_enhanced_select_params.i18n_input_too_short_1;
                    }

                    return wc_enhanced_select_params.i18n_input_too_short_n.replace('%qty%', remainingChars);
                },
                loadingMore: function () {
                    return wc_enhanced_select_params.i18n_load_more;
                },
                maximumSelected: function (args) {
                    if (args.maximum === 1) {
                        return wc_enhanced_select_params.i18n_selection_too_long_1;
                    }

                    return wc_enhanced_select_params.i18n_selection_too_long_n.replace('%qty%', args.maximum);
                },
                noResults: function () {
                    return wc_enhanced_select_params.i18n_no_matches;
                },
                searching: function () {
                    return wc_enhanced_select_params.i18n_searching;
                }
            }
        };
    }

    try {
        $(document.body)
            .on('wc-enhanced-select-init', function () {
                $(':button.add_groups').filter(':not(.enhanced)').each(function () {
                    $(this).click(function () {
                        //Add the groups
                    });
                    $(this).addClass('enhanced');
                });
                // Ajax product search box
                $('select.wc4bp-group-search').filter(':not(.enhanced)').each(function () {
                    var select2_args = {
                        allowClear: $(this).data('allow_clear') ? true : false,
                        placeholder: $(this).data('placeholder'),
                        minimumInputLength: $(this).data('minimum_input_length') ? $(this).data('minimum_input_length') : '3',
                        escapeMarkup: function (m) {
                            return m;
                        },
                        ajax: {
                            url: wc4bp_groups.ajax_url,
                            dataType: 'json',
                            quietMillis: 250,
                            data: function (term) {
                                return {
                                    term: term,
                                    action: $(this).data('action'),
                                    security: wc4bp_groups.search_groups_nonce,
                                    exclude: $(this).data('exclude'),
                                    include: $(this).data('include'),
                                    limit: $(this).data('limit')
                                };
                            },
                            processResults: function (data) {
                                var terms = [];
                                if (data) {
                                    $.each(data, function (id, text) {
                                        terms.push({id: id, text: text});
                                    });
                                }
                                return {
                                    results: terms
                                };
                            },
                            cache: true
                        }
                    };

                    select2_args = $.extend(select2_args, getEnhancedSelectFormatString());

                    $(this).select2(select2_args).addClass('enhanced');

                    if ($(this).data('sortable')) {
                        var $select = $(this);
                        var $list = $(this).next('.select2-container').find('ul.select2-selection__rendered');

                        $list.sortable({
                            placeholder: 'ui-state-highlight select2-selection__choice',
                            forcePlaceholderSize: true,
                            items: 'li:not(.select2-search__field)',
                            tolerance: 'pointer',
                            stop: function () {
                                $($list.find('.select2-selection__choice').get().reverse()).each(function () {
                                    var id = $(this).data('data').id;
                                    var option = $select.find('option[value="' + id + '"]')[0];
                                    $select.prepend(option);
                                });
                            }
                        });
                    }
                });
            })
            .trigger('wc-enhanced-select-init');

        function wc4bp_add_groups() {

            function save_groups() {
                var json_handler = jQuery('#_wc4bp_groups_json');
                var groups = jQuery('.wc4bp-group-item').map(function (i, v) {
                    var member_type = jQuery('#_membership_level', this),
                        optional = jQuery('#_membership_optional', this),
                        woo_trigger = jQuery('#_trigger', this);
                    return {
                        'group_id': jQuery(this).attr('group_id'),
                        'group_name': jQuery(this).attr('group_name'),
                        'member_type': member_type.val(),
                        'is_optional': optional.val(),
                        'trigger': woo_trigger.val()
                    }
                }).get();

                var json = JSON.stringify(groups);
                json_handler.val(json);
            }

            function add_group() {
                var searched = jQuery('#wc4bp-group-ids').select2('data');
                if (searched) {
                    var group_item = jQuery('.wc4bp-group-item'),
                        inserted = false;
                    var existing = group_item.map(function (i, v) {
                        jQuery(this).removeClass('wc4bp-group-error');
                        return jQuery(this).attr('group_id');
                    }).get();
                    $.each(searched, function (index, value) {
                        var exist = false, current_group = {id: value['id'], text: value['text']};
                        $.each(existing, function (i, v) {
                            if (current_group['id'] === v) {
                                exist = true;
                                jQuery('#wc4bp_item_' + v).addClass('wc4bp-group-error');
                                return false;
                            }
                        });
                        if (!exist) {
                            insert_container(current_group);
                        }
                    });
                }
                else {
                    alert('Need to select some groups to add');
                }
            }

            function insert_container(item) {
                var container = jQuery('.wc4bp-group-container');
                jQuery(".wc4bp-group-loading").attr('style', 'display:inline-block');
                jQuery.post(wc4bp_groups.ajax_url, {
                    'action': 'wc4bp_get_group_view',
                    'group': JSON.stringify(item),
                    'security': wc4bp_groups.search_groups_nonce
                }, function (data) {
                    if (data || data === '0') {
                        container.append(data);
                    }
                    else {
                        alert(wc4bp_groups.general_error);
                    }
                }).fail(function () {
                    alert(wc4bp_groups.general_error);
                }).always(function () {
                    jQuery(".wc4bp-group-loading").attr('style', 'display:none');
                });
            }

            function clean_error(e) {
                jQuery(this).removeClass('wc4bp-group-error');
            }

            function remove_item(e) {
                e.preventDefault();
                var group_id = jQuery(this).attr('group_id');
                jQuery('#wc4bp_item_' + group_id).remove();
            }

            return {
                init: function () {
                    if (document.getElementById('post') !== null || document.getElementsByClassName('entry-content') !== null) {
                        // Bind event handlers for form Settings page
                        add_groups_var.formActionsInit();
                    }
                },

                formActionsInit: function () {
                    var items_container = jQuery('.wc4bp-group-container');
                    if (wc4bp_groups.is_force === undefined || wc4bp_groups.is_force === "") {
                        jQuery('#post').on('submit', save_groups);
                    }
                    else {
                        jQuery('.bf-submit').click(save_groups);
                    }

                    jQuery('.add_groups').click(add_group);
                    items_container.on('click', '.wc4bp-group-item', clean_error);
                    items_container.on('click', '.wc4bp-group-group-remove', remove_item);
                }
            };
        }

        var add_groups_var = wc4bp_add_groups();
        jQuery(document).ready(function ($) {
            add_groups_var.init();
        });

    } catch (err) {
        // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
        window.console.log(err);
    }
});
