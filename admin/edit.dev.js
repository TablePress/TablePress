/**
 *
 *
 * @since 1.0.0
 */

jQuery(document).ready( function( $ ) {
	var tp = {
		made_changes: false,
		table: {
			id: $( '#table-id' ).val(),
			orig_id: $( '#table-orig-id' ).val(),
			rows: $( '#number-rows' ).val(),
			columns: $( '#number-columns' ).val(),
			head: $( '#option-table-head' ).prop( 'checked' ),
			foot: $( '#option-table-foot' ).prop( 'checked' ),
			no_data_columns_pre: 2,
			no_data_columns_post: 1,
			body_cells_pre: '<tr><td><span class="move-handle"></span></td><td><input type="checkbox" /><input type="hidden" class="visibility" name="table[visibility][rows][]" value="1" /></td>',
			body_cells_post: '<td><span class="move-handle"></span></td></tr>',
			body_cell: '<td><textarea rows="1"></textarea></td>',
			head_cell: '<th class="head"><span class="sort-control sort-desc" title="' + tablepress_strings.sort_desc + '"></span><span class="sort-control sort-asc" title="' + tablepress_strings.sort_asc + '"></span><span class="move-handle"></span></th>',
			foot_cell: '<th><input type="checkbox" /><input type="hidden" class="visibility" name="table[visibility][columns][]" value="1" /></th>',
			set_table_changed: function() {
				tp.made_changes = true;
			},
			unset_table_changed: function() {
				tp.made_changes = false;
				$( '#table-preview' ).empty(); // clear preview
				$( '#edit-form-body' ).one( 'change', 'textarea', tp.table.set_table_changed );
			},
			change_id: function( /* event */ ) {
				if ( this.value == tp.table.id )
					return;

				if ( confirm( tablepress_strings.ays_change_table_id ) ) {
					tp.table.id = this.value;
					$( '.table-shortcode' ).val( '[table id=' + tp.table.id + ' /]' ).click(); // click() to focus and select
					tp.table.set_table_changed();
				} else {
					$(this).val( tp.table.id );
				}
			},
			change_table_head: function( /* event */ ) {
				tp.table.head = $(this).prop( 'checked' );
				tp.rows.stripe();
			},
			change_table_foot: function( /* event */ ) {
				tp.table.foot = $(this).prop( 'checked' );
				tp.rows.stripe();
			},
			prepare_ajax_request: function( wp_action, wp_nonce ) {
				var $table_body = $( '#edit-form-body' ),
					table_data = [],
					table_options,
					table_visibility = { rows: [], columns: [], hidden_rows: 0, hidden_columns: 0 };

				$table_body.children().each( function( idx, row ) {
					table_data[idx] = $(row).find( 'textarea' )
						.map( function() {
							return $(this).val();
						} )
						.get();
				} );
				table_data = JSON.stringify( table_data );

				// evtl. für options-saving: http://stackoverflow.com/questions/1184624/serialize-form-to-json-with-jquery
				table_options = {
					table_head: tp.table.head,
					table_foot: tp.table.foot
				};
				table_options = JSON.stringify( table_options );

				table_visibility.rows = $table_body.find( ':hidden' )
					.map( function() {
						if ( '1' == $(this).val() )
							return 1;
						table_visibility.hidden_rows += 1;
						return 0;
					} )
					.get();
				table_visibility.columns = $( '#edit-form-foot' ).find( ':hidden' )
					.map( function() {
						if ( '1' == $(this).val() )
							return 1;
						table_visibility.hidden_columns += 1;
						return 0;
					} )
					.get();
				table_visibility = JSON.stringify( table_visibility );

				// request_data =
				return {
					action: wp_action,
					_ajax_nonce : $( wp_nonce ).val(),
					tablepress: {
						id: tp.table.id,
						orig_id: tp.table.orig_id,
						name: $( '#table-name' ).val(),
						description: $( '#table-description' ).val(),
						rows: tp.table.rows,
						columns: tp.table.columns,
						data: table_data,
						options: table_options,
						visibility: table_visibility
					}
				};
			},
			preview: {
				trigger: function( /* event */ ) {
					if ( ! tp.made_changes && $( '#table-preview' ).children().length ) {
						tp.table.preview.show();
						return;
					}

					$(this).after( '<span class="animation-preview" title="' + tablepress_strings.preparing_preview + '"/>' );
					$( '.show-preview-button' ).prop( 'disabled', true );
					$( 'body' ).addClass( 'wait' );

					$.post(
							ajaxurl,
							tp.table.prepare_ajax_request( 'tablepress_preview_table', '#nonce-preview-table' ),
							function() { /* done with .success() below */ },
							'json'
						)
						.success( tp.table.preview.ajax_success )
						.error( tp.table.preview.ajax_error );
				},
				ajax_success: function( data, status, jqXHR ) {
					if ( ( 'undefined' == typeof status ) || ( 'success' != status ) )
						tp.table.preview.error( 'AJAX call successful, but unclear status' );
					else if ( ( 'undefined' == typeof data ) || ( null == data ) || ( '-1' == data ) || ( 'undefined' == typeof data.success ) || ( true !== data.success ) )
						tp.table.preview.error( 'AJAX call successful, but unclear data' );
					else
						tp.table.preview.success( data );
				},
				ajax_error: function( jqXHR, status, error_thrown ) {
					tp.table.preview.error( 'AJAX call failed: ' + status + ' - ' + error_thrown );
				},
				success: function( data ) {
					$( '#table-preview' ).empty();
					$( '<iframe id="table-preview-iframe" />' ).load( function() {
						var $iframe = $(this).contents();
						$iframe.find( 'head' ).append( data.head_html );
						$iframe.find( 'body' ).append( data.body_html );
					} ).appendTo( '#table-preview' );
					$( '.animation-preview' ).remove();
					$( '.show-preview-button' ).prop( 'disabled', false );
					$( 'body' ).removeClass( 'wait' );
					tp.table.preview.show();
				},
				error: function( message ) {
					$( '.animation-preview' )
						.after( '<span class="preview-error">' + tablepress_strings.preview_error + ' ' + message + '</span>' )
						.remove();
					$( '.preview-error' ).delay( 2000 ).fadeOut( 2000, function() { $(this).remove(); } );
					$( '.show-preview-button' ).prop( 'disabled', false );
					$( 'body' ).removeClass( 'wait' );
				},
				show: function() {
					var width = $(window).width() - 120,
						height = $(window).height() - 120;
					if ( $( 'body.admin-bar' ).length )
						height -= 28;
					tb_show( tablepress_strings.preview, '#TB_inline?height=' + height + '&width=' + width + '&inlineId=preview-container', false );
				}
			}
		},
		rows: {
			create: function( num_rows ) {
				var i, j,
					column_idxs,
					new_rows = '';

				for ( i = 0; i < num_rows; i++ ) {
					new_rows += tp.table.body_cells_pre;
					for ( j = 0; j < tp.table.columns; j++ )
						new_rows += tp.table.body_cell;
					new_rows += tp.table.body_cells_post;
				}

				column_idxs = $( '#edit-form-foot' ).find( '.column-hidden' )
					.map( function() { return $(this).index(); } ).get();
				return $( new_rows ).each( function( row_idx, row ) {
					$(row).children()
						.filter( function( idx ) { return ( -1 != jQuery.inArray( idx, column_idxs ) ); } )
						.addClass( 'column-hidden' );
				} );
			},
			append: function( /* event */ ) {
				var num_rows = $( '#rows-append-number' ).val();

				if ( ! ( /^[1-9][0-9]{0,4}$/ ).test( num_rows ) ) {
					alert( tablepress_strings.append_num_rows_invalid );
					$( '#rows-append-number' ).focus().select();
					return;
				}

				$( '#edit-form-body' ).append( tp.rows.create( num_rows ) );

				tp.rows.stripe();
				tp.reindex();
			},
			insert: function( event ) {
				var $selected_rows = $( '#edit-form-body' ).find( 'input:checked' )
					.prop( 'checked', event.altKey ).closest( 'tr' );

				if ( 0 === $selected_rows.length ) {
					alert( tablepress_strings.no_rows_selected );
					return;
				}

				$selected_rows.before( tp.rows.create( 1 ) );

				tp.rows.stripe();
				tp.reindex();
			},
			hide: function( event ) {
				var $selected_rows = $( '#edit-form-body' ).find( 'input:checked' )
					.prop( 'checked', event.altKey ).closest( 'tr' );

				if ( 0 === $selected_rows.length ) {
					alert( tablepress_strings.no_rows_selected );
					return;
				}

				$selected_rows.addClass( 'row-hidden' ).find( '.visibility' ).val( '0' );

				tp.rows.stripe();
				tp.table.set_table_changed();
			},
			unhide: function( event ) {
				var $selected_rows = $( '#edit-form-body' ).find( 'input:checked' )
					.prop( 'checked', event.altKey ).closest( 'tr' );

				if ( 0 === $selected_rows.length ) {
					alert( tablepress_strings.no_rows_selected );
					return;
				}

				$selected_rows
					.removeClass( 'row-hidden' )
					.find( '.visibility' ).val( '1' );

				tp.rows.stripe();
				tp.table.set_table_changed();
			},
			remove: function( /* event */ ) {
				var $selected_rows = $( '#edit-form-body' ).find( 'input:checked' ).closest( 'tr' );

				if ( 0 === $selected_rows.length ) {
					alert( tablepress_strings.no_rows_selected );
					return;
				}

				if ( tp.table.rows == $selected_rows.length ) {
					alert( tablepress_strings.no_remove_all_rows );
					return;
				}

				if ( ! confirm( tablepress_strings.ays_remove_rows ) )
					return;

				$selected_rows.remove();

				tp.rows.stripe();
				tp.reindex();
			},
			move: {
				start: function( event, ui ) {
					$( ui.placeholder ).removeClass( 'row-hidden' ).css( 'visibility', 'visible' )
						.html( '<td colspan="' + ( tp.table.columns + tp.table.no_data_columns_pre + tp.table.no_data_columns_post ) + '"><div/></td>' );
					$( ui.helper ).removeClass( 'odd head-row foot-row' );
				},
				change: function( event, ui ) {
					tp.rows.stripe( ui.helper );
				},
				stop: function( /* event, ui */ ) {
					tp.rows.stripe();
				}
			},
			sort: function() {
				var column_idx = $(this).parent().index(),
					direction = ( $(this).hasClass( 'sort-asc' ) ) ? 1 : -1,
					$table_body = $('#edit-form-body'),
					$head_rows = $table_body.find( '.head-row' ).prevAll().andSelf(),
					$foot_rows = $table_body.find( '.foot-row' ).nextAll().andSelf(),
					rows = $table_body.children().not( $head_rows ).not( $foot_rows ).get(),
					/*
					 * Natural Sort algorithm for Javascript - Version 0.6 - Released under MIT license
					 * Author: Jim Palmer (based on chunking idea from Dave Koelle)
					 * Contributors: Mike Grier (mgrier.com), Clint Priest, Kyle Adams, guillermo
					 * See: http://js-naturalsort.googlecode.com/ and http://www.overset.com/2008/09/01/javascript-natural-sort-algorithm-with-unicode-support/
					 */
					natural_sort = function( a, b ) {
						var re = /(^-?[0-9]+(\.?[0-9]*)[df]?e?[0-9]?$|^0x[0-9a-f]+$|[0-9]+)/gi,
							sre = /(^[ ]*|[ ]*$)/g,
							dre = /(^([\w ]+,?[\w ]+)?[\w ]+,?[\w ]+\d+:\d+(:\d+)?[\w ]?|^\d{1,4}[\/\-]\d{1,4}[\/\-]\d{1,4}|^\w+, \w+ \d+, \d{4})/,
							hre = /^0x[0-9a-f]+$/i,
							ore = /^0/,
							// convert all to strings and trim()
							x = a.toString().replace(sre, '') || '',
							y = b.toString().replace(sre, '') || '',
							// chunk/tokenize
							xN = x.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
							yN = y.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
							// numeric, hex or date detection
							xD = parseInt(x.match(hre)) || (xN.length != 1 && x.match(dre) && Date.parse(x)),
							yD = parseInt(y.match(hre)) || xD && y.match(dre) && Date.parse(y) || null;
						// first try and sort Hex codes or Dates
						if (yD) {
							if ( xD < yD ) return -1;
							else if ( xD > yD )	return 1;
						}
						// natural sorting through split numeric strings and default strings
						for(var cLoc=0, numS=Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
							// find floats not starting with '0', string or 0 if not defined (Clint Priest)
							oFxNcL = !(xN[cLoc] || '').match(ore) && parseFloat(xN[cLoc]) || xN[cLoc] || 0;
							oFyNcL = !(yN[cLoc] || '').match(ore) && parseFloat(yN[cLoc]) || yN[cLoc] || 0;
							// handle numeric vs string comparison - number < string - (Kyle Adams)
							if (isNaN(oFxNcL) !== isNaN(oFyNcL)) return (isNaN(oFxNcL)) ? 1 : -1;
							// rely on string comparison if different types - i.e. '02' < 2 != '02' < '2'
							else if (typeof oFxNcL !== typeof oFyNcL) {
								oFxNcL += '';
								oFyNcL += '';
							}
							if (oFxNcL < oFyNcL) return -1;
							if (oFxNcL > oFyNcL) return 1;
						}
						return 0;
					};

				$.each( rows, function( row_idx, row ) {
					//parseFloat???
					row.sort_key = $(row).children().eq( column_idx ).find( 'textarea' ).val().toUpperCase();
				} );

				rows.sort( function( a, b ) {
					return direction * natural_sort( a.sort_key, b.sort_key );
				} );

				// might not be necessary:
				$.each( rows, function( row_idx, row ) {
					row.sort_key = null;
				} );

				$table_body.append( $head_rows );
				$table_body.append( rows );
				$table_body.append( $foot_rows );

				tp.rows.stripe();
				tp.reindex();
			},
			stripe: function( helper ) {
				if ( 'undefined' == typeof helper )
					helper = null;
				helper = $( helper );
				var $rows = $( '#edit-form-body' ).children().removeClass( 'odd head-row foot-row' ).not( helper );
				$rows.filter( ':even' ).addClass( 'odd' );
				$rows = $rows.not( '.row-hidden' );
				if( helper.hasClass( 'row-hidden' ) )
					$rows = $rows.not( '.ui-sortable-placeholder' );
				if ( tp.table.head )
					$rows.first().addClass( 'head-row' );
				if ( tp.table.foot )
					$rows.last().addClass( 'foot-row' );
			}
		},
		columns: {
			append: function( /* event */ ) {
				var i,
					num_columns = $( '#columns-append-number' ).val(),
					new_body_cells = new_head_cells = new_foot_cells = '';

				if ( ! ( /^[1-9][0-9]{0,4}$/ ).test( num_columns ) ) {
					alert( tablepress_strings.append_num_columns_invalid );
					$( '#columns-append-number' ).focus().select();
					return;
				}

				for ( i = 0; i < num_columns; i++ ) {
					new_body_cells += tp.table.body_cell;
					new_head_cells += tp.table.head_cell;
					new_foot_cells += tp.table.foot_cell;
				}

				$( '#edit-form-body' ).children().each( function( row_idx, row ) {
					$(row).children().slice( - tp.table.no_data_columns_post )
						.before( new_body_cells );
				} );
				$( '#edit-form-head' ).children().slice( - tp.table.no_data_columns_post )
					.before( new_head_cells );
				$( '#edit-form-foot' ).children().slice( - tp.table.no_data_columns_post )
					.before( new_foot_cells );

				tp.reindex();
			},
			insert: function( event ) {
				var column_idxs,
					$selected_columns = $( '#edit-form-foot' ).find( 'input:checked' )
						.prop( 'checked', event.altKey ).closest( 'th' );

				if ( 0 === $selected_columns.length ) {
					alert( tablepress_strings.no_columns_selected );
					return;
				}

				column_idxs = $selected_columns.map( function() { return $(this).index(); } ).get();
				$( '#edit-form-body' ).children().each( function( row_idx, row ) {
					$(row).children()
						.filter( function( idx ) { return ( -1 != jQuery.inArray( idx, column_idxs ) ); } )
						.before( tp.table.body_cell );
				} );
				$( '#edit-form-head' ).children()
					.filter( function( idx ) { return ( -1 != jQuery.inArray( idx, column_idxs ) ); } )
					.before( tp.table.head_cell );
				$selected_columns.before( tp.table.foot_cell );

				tp.reindex();
			},
			hide: function( event ) {
				var column_idxs,
					$selected_columns = $( '#edit-form-foot' ).find( 'input:checked' )
						.prop( 'checked', event.altKey ).closest( 'th' );

				if ( 0 === $selected_columns.length ) {
					alert( tablepress_strings.no_columns_selected );
					return;
				}

				column_idxs = $selected_columns.map( function() { return $(this).index(); } ).get();
				$( '#edit-form-body' ).children().add( '#edit-form-head' ).each( function( row_idx, row ) {
					$(row).children()
						.filter( function( idx ) { return ( -1 != jQuery.inArray( idx, column_idxs ) ); } )
						.addClass( 'column-hidden' );
				} );
				$selected_columns.addClass( 'column-hidden' ).find( '.visibility' ).val( '0' );

				tp.table.set_table_changed();
			},
			unhide: function( event ) {
				var column_idxs,
					$selected_columns = $( '#edit-form-foot' ).find( 'input:checked' )
						.prop( 'checked', event.altKey ).closest( 'th' );

				if ( 0 === $selected_columns.length ) {
					alert( tablepress_strings.no_columns_selected );
					return;
				}

				column_idxs = $selected_columns.map( function() { return $(this).index(); } ).get();
				$( '#edit-form-body' ).children().add( '#edit-form-head' ).each( function( row_idx, row ) {
					$(row).children()
						.filter( function( idx ) { return ( -1 != jQuery.inArray( idx, column_idxs ) ); } )
						.removeClass( 'column-hidden' );
				} );
				$selected_columns.removeClass( 'column-hidden' ).find( '.visibility' ).val( '1' );

				tp.table.set_table_changed();
			},
			remove: function( /* event */ ) {
				var column_idxs,
					$selected_columns = $( '#edit-form-foot' ).find( 'input:checked' ).closest( 'th' );

				if ( 0 === $selected_columns.length ) {
					alert( tablepress_strings.no_columns_selected );
					return;
				}

				if ( tp.table.columns == $selected_columns.length ) {
					alert( tablepress_strings.no_remove_all_columns );
					return;
				}

				if ( ! confirm( tablepress_strings.ays_remove_columns ) )
					return;

				column_idxs = $selected_columns.map( function() { return $(this).index(); } ).get();
				$( '#edit-form-body' ).children().add( '#edit-form-head' ).each( function( row_idx, row ) {
					$(row).children()
						.filter( function( idx ) { return ( -1 != jQuery.inArray( idx, column_idxs ) ); } )
						.remove();
				} );
				$selected_columns.remove();

				tp.reindex();
			},
			move: {
				source_idx: -1,
				target_idx: -1,
				$rows: null,
				$row_children: null,
				$cell: null,
				$cells: null,
				$placeholder: null,
				$helper: null,
				start: function( event, ui ) {
					var $item = $( ui.item ),
						column_width;

					tp.columns.move.source_idx = $item.index();

					tp.columns.move.$rows = $( '#edit-form-body' ).children().add( '#edit-form-foot' );

					tp.columns.move.$cells = tp.columns.move.$rows
						.find( ':nth-child(' + ( tp.columns.move.source_idx + 1 ) + ')' )
						.each( function() {
							tp.columns.move.$cell = $(this);
							$( '<td class="move-placeholder"><div/></td>' ).insertBefore( tp.columns.move.$cell );
							tp.columns.move.$cell.insertAfter( tp.columns.move.$cell.nextAll().last() )
								.clone().addClass( 'move-hover' ).insertAfter( tp.columns.move.$cell )
								.find( 'textarea' ).val( tp.columns.move.$cell.find( 'textarea' ).val() );
								// last line works around problem with clone() of textareas, see jQuery bugs 5524, 2285, 3016
						} )
						.hide();

					tp.columns.move.$helper = tp.columns.move.$rows.find( '.move-hover' );
					/* // seems not to be working for rows, so disable it for columns
						.each( function() {
							tp.columns.move.$cell = $(this);
							tp.columns.move.$cell.css( 'top', ( tp.columns.move.$cell.position().top - 3 ) + 'px' );
						} );
					*/

					column_width = tp.columns.move.$helper.eq(1).width(); // eq(0) is table foot
					tp.columns.move.$helper.eq(0).width( column_width );
					tp.columns.move.$placeholder = tp.columns.move.$rows.find( '.move-placeholder' );
					tp.columns.move.$placeholder.find( 'div' ).width( column_width );
				},
				change: function( event, ui ) {
					tp.columns.move.target_idx = $( ui.placeholder ).index();

					if ( ( tp.columns.move.target_idx - tp.columns.move.source_idx ) == 1 )
						tp.columns.move.target_idx += 1;
					else
						if ( tp.columns.move.target_idx == tp.columns.move.source_idx )
							tp.columns.move.target_idx -= 1;

					tp.columns.move.$placeholder.each( function() {
						tp.columns.move.$cell = $(this);
						tp.columns.move.$cell.insertBefore( tp.columns.move.$cell.parent().children().eq( tp.columns.move.target_idx ) );
					} );

					if ( tp.columns.move.target_idx > tp.columns.move.source_idx )
						tp.columns.move.target_idx -= 1;

					tp.columns.move.source_idx = tp.columns.move.target_idx;
				},
				sort: function( event, ui ) {
					tp.columns.move.$helper.css( 'left', ui.position.left );
				},
				stop: function( /* event, ui */ ) {
					tp.columns.move.$helper.remove();
					tp.columns.move.$cells
						.each( function() {
							tp.columns.move.$cell = $(this);
							tp.columns.move.$cell.insertBefore( tp.columns.move.$cell.parent().find( '.move-placeholder' ) );
						} )
						.show();
					tp.columns.move.$placeholder.remove();

					tp.columns.move.source_idx = tp.columns.move.target_idx = -1;
					tp.columns.move.$rows = tp.columns.move.$row_children = tp.columns.move.$cell
					= tp.columns.move.$cells = tp.columns.move.$placeholder = tp.columns.move.$helper
					= null;

					tp.reindex();
				}
			},
			number_to_letter: function( number ) {
				var column = '';
				while ( number > 0 ) {
					column = String.fromCharCode( 65 + ( ( number-1) % 26 ) ) + column;
					number = Math.floor( (number-1) / 26 );
				}
				return column;
			}/*,
			letter_to_number: function( column ) {
				column = column.toUpperCase();
				var count = column.length,
					number = 0,
					i;
				for ( i = 0; i < count; i++ ) {
					number += ( column.charCodeAt( count-1-i ) - 64 ) * Math.pow( 26, i );
				}
				return number;
			}*/
		},
		cells: {
			$focus: $( null ),
			autogrow: function( /* event */ ) {
				tp.cells.$focus.removeClass( 'focus' );
				tp.cells.$focus = $(this).closest( 'tr' ).addClass( 'focus' );
			},
			advanced_editor: {
				$textarea: null,
				keyopen: function( event ) {
					// evtl. lieber event.shiftKey oder event.ctrlKey
					if ( ! event.altKey )
						return;

					var $advanced_editor = $( '#advanced-editor-content' );

					tp.cells.$textarea = $(this).blur();
					$advanced_editor.val( tp.cells.$textarea.val() );
					tb_show( 'Visual Editor', '#TB_inline?height=287&width=600&inlineId=advanced-editor-container&modal=true', false );
					$advanced_editor.get(0).selectionStart = $advanced_editor.get(0).selectionEnd = $advanced_editor.val().length;
					$advanced_editor.focus();
				},
				buttonopen: function() {
					if ( ! confirm( tablepress_strings.advanced_editor_open ) )
						return;

					$( '#edit-form-body' ).one( 'click', 'textarea', function() {
						var $advanced_editor = $( '#advanced-editor-content' );

						tp.cells.$textarea = $(this).blur();
						$advanced_editor.val( tp.cells.$textarea.val() );
						tb_show( 'Visual Editor', '#TB_inline?height=287&width=600&inlineId=advanced-editor-container&modal=true', false );
						$advanced_editor.get(0).selectionStart = $advanced_editor.get(0).selectionEnd = $advanced_editor.val().length;
						$advanced_editor.focus();
					} );
				},
				save: function() {
					var $ve_content = $( '#advanced-editor-content' ).blur().val();
					if ( tp.cells.$textarea.val() != $ve_content ) {
						tp.cells.$textarea.val( $ve_content );
						// position cursor at the end
						tp.cells.$textarea.get(0).selectionStart = tp.cells.$textarea.get(0).selectionEnd = tp.cells.$textarea.val().length;
						tp.table.set_table_changed();
					}
					tp.cells.$textarea.focus();
					tp.cells.advanced_editor.close();
				},
				close: function() {
					tb_remove();
				}
			},
			checkboxes: {
				last_clicked: { '#edit-form-body' : false, '#edit-form-foot' : false },
				multi_select: function ( event ) {
					if ( 'undefined' == event.shiftKey )
						return true;

					if ( event.shiftKey ) {
						if ( ! tp.cells.checkboxes.last_clicked[ event.data.parent ] )
							return true;

						var $checkboxes = $( event.data.parent ).find( ':checkbox' ),
							first_cb = $checkboxes.index( tp.cells.checkboxes.last_clicked[ event.data.parent ] ),
							last_cb = $checkboxes.index( this );
						if ( first_cb != last_cb ) {
							$checkboxes.slice( Math.min( first_cb, last_cb ), Math.max( first_cb, last_cb ) ).prop( 'checked', $(this).prop( 'checked' ) );
						}
					}
					tp.cells.checkboxes.last_clicked[ event.data.parent ] = this;
					return true;
				}
			}
		},
		content: {
			link: {
				add: function( /* event */ ) {
					if ( ! confirm( tablepress_strings.link_add ) )
						return;

					// mousedown instead of click to allow selection of text
					// mousedown will set the desired target textarea, and mouseup anywhere will show the link box
					// other approaches can lead to the wrong textarea being selected
					$( '#edit-form-body' ).one( 'mousedown', 'textarea', function() {
						wpActiveEditor = this.id;
						$( window ).one( 'mouseup', function() {
							wpLink.open();
							tp.table.set_table_changed();
						} );
					} );
				}
			},
			image: {
				add: function( /* event */ ) {
					if ( confirm( tablepress_strings.image_add ) )
						$( '#edit-form-body' ).one( 'mousedown', 'textarea', function() {
							wpActiveEditor = this;
							var $link = $( '#image-add' ),
								width = $(window).width(),
								W = ( 720 < width ) ? 720 : width,
								H = $(window).height();
							if ( $( 'body.admin-bar' ).length )
								H -= 28;
							tb_show( $link.attr( 'title' ), $link.attr( 'href' ) + '&TB_iframe=true&height=' + ( H - 85 ) + '&width=' + ( W - 80 ), false );
							$(this).blur();
							tp.table.set_table_changed(); // this should actually be done in send_to_editor()
						} );

					return false;
				}
			},
			span: {
				add: function( span ) {
					// todo: Frage entsprechend des span-Typs
					if ( ! confirm( tablepress_strings.span_add ) )
						return;

					$( '#edit-form-body' ).one( 'click', 'textarea', function() {
						var $textarea = $(this),
							col_idx = $textarea.parent().index(),
							row_idx = $textarea.closest( 'tr' ).index();
						if ( ( '#rowspan#' == span ) && ( 0 == row_idx ) ) {
							alert( tablepress_strings.no_rowspan_first_row );
							return;
						} else if ( ( '#colspan#' == span ) && ( tp.table.no_data_columns_pre == col_idx ) ) {
							alert( tablepress_strings.no_colspan_first_col );
							return;
						}
						$textarea.val( span );
						tp.table.set_table_changed();
					} );
				}
			}
		},
		check: {
			table_id: function( event ) {
				if ( ( 37 == event.which ) || ( 39 == event.which ) )
					return;
				var $input = $(this);
				$input.val( $input.val().replace( /[^0-9a-zA-Z-_]/g, '' ) );
			},
			changes_saved: function() {
				if ( tp.made_changes )
					return tablepress_strings.unsaved_changes_unload;
			}
		},
		reindex: function() {
			var $row,
				$rows = $( '#edit-form-body' ).children(),
				$cell, known_references = {};

			tp.table.rows = $rows.length;
			if ( tp.table.rows > 0 )
				tp.table.columns = $rows.first().children().length - tp.table.no_data_columns_pre - tp.table.no_data_columns_post;
			else
				tp.table.columns = 0;

			$rows
			.each( function( row_idx, row ) {
				$row = $( row );
				$row.find( 'textarea' )
					.val( function( column_idx, value ) {
						// If the cell is not a formula, there's nothing to do here
						if ( ( '' == value ) || ( '=' != value.charAt(0) ) )
							return value;

						return value.replace( /\[([a-z]+[0-9]+)(?::([a-z]+[0-9]+))?\]/gi, function( full_match, first_cell, second_cell ) {
							// first_cell must always exist, while second_cell only exists in ranges like [A4:B7]
							// we will use full_match as our result variable, so that we don't need an extra one

							if ( ! known_references.hasOwnProperty( first_cell ) ) {
								$cell = $( '#cell-' + first_cell.toUpperCase() );
								if ( $cell.length )
									known_references[ first_cell ] = tp.columns.number_to_letter( $cell.parent().index() - tp.table.no_data_columns_pre + 1 ) + ( $cell.closest( 'tr' ).index() + 1 );
								else
									known_references[ first_cell ] = first_cell;
							}
							full_match = '[' + known_references[ first_cell ];

							if ( 'undefined' != typeof second_cell ) {
								if ( ! known_references.hasOwnProperty( second_cell ) ) {
									$cell = $( '#cell-' + second_cell.toUpperCase() );
									if ( $cell.length )
										known_references[ second_cell ] = tp.columns.number_to_letter( $cell.parent().index() - tp.table.no_data_columns_pre + 1 ) + ( $cell.closest( 'tr' ).index() + 1 );
									else
										known_references[ second_cell ] = second_cell;
								}
								full_match += ':' + known_references[ second_cell ];
							}

							return full_match + ']';
						} );
					} )
					.attr( 'name', function( column_idx /*, old_name */ ) {
						return 'table[data][' + row_idx + '][' + column_idx + ']';
					} );

				$row.find( '.move-handle' ).html( row_idx + 1 );
			} )
			.each( function( row_idx, row ) {
				$( row ).find( 'textarea' ).attr( 'id', function( column_idx /*, old_id */ ) {
					return 'cell-' + tp.columns.number_to_letter( column_idx + 1 ) + ( row_idx + 1 );
				} );
			});
			$( '#edit-form-head' ).find( '.move-handle' )
				.html( function( idx ) { return tp.columns.number_to_letter( idx + 1 ); } );

			$( '#number-rows' ).val( tp.table.rows );
			$( '#number-columns' ).val( tp.table.columns );

			tp.table.set_table_changed();
		},
		save_changes: {
			trigger: function( event ) {
				if ( event.altKey ) {
					tp.made_changes = false; // to prevent onunload warning
					$(this).closest( 'form' ).submit();
					return;
				}

				$(this).after( '<span class="animation-saving" title="' + tablepress_strings.saving_changes + '"/>' );
				$( '.save-changes-button' ).prop( 'disabled', true );
				$( 'body' ).addClass( 'wait' );

				$.post(
						ajaxurl,
						tp.table.prepare_ajax_request( 'tablepress_save_table', '#nonce-edit-table' ),
						function() { /* done with .success() below */ },
						'json'
					)
					.success( tp.save_changes.ajax_success )
					.error( tp.save_changes.ajax_error );
			},
			ajax_success: function( data, status, jqXHR ) {
				if ( ( 'undefined' == typeof status ) || ( 'success' != status ) )
					tp.save_changes.error( 'AJAX call successful, but unclear status' );
				else if ( ( 'undefined' == typeof data ) || ( null == data ) || ( '-1' == data ) || ( 'undefined' == typeof data.success ) || ( true !== data.success ) )
					tp.save_changes.error( 'AJAX call successful, but unclear data' );
				else
					tp.save_changes.success( data );
			},
			ajax_error: function( jqXHR, status, error_thrown ) {
				tp.save_changes.error( 'AJAX call failed: ' + status + ' - ' + error_thrown );
			},
			success: function( data ) {
				// saving was successful, so the original ID has changed to the (maybe) new ID -> we need to adjust all occurances
				// update URL (for HTML5 browsers only)
				if ( ( 'pushState' in window.history ) && null !== window.history['pushState'] )
					window.history.pushState( '', '', window.location.href.replace( /table_id=[0-9a-zA-Z-_]+/gi, 'table_id=' + data.table_id ) );
				// update table ID in input fields (type text and hidden)
				tp.table.orig_id = tp.table.id = data.table_id;
				$( '#table-orig-id' ).val( tp.table.orig_id );
				$( '#table-id' ).val( tp.table.id );
				// update the Shortcode text field
				$( '.table-shortcode' ).val( '[table id=' + tp.table.id + ' /]' );
				// update the nonces
				$( '#nonce-edit-table' ).val( data.new_edit_nonce );
				$( '#nonce-preview-table' ).val( data.new_preview_nonce );
				// update last modified date and user nickname
				$( '#last-modified' ).text( data.last_modified );
				$( '#last-editor' ).text( data.last_editor );
				tp.table.unset_table_changed();
				tp.save_changes.after_saving_dialog( 'success', tablepress_strings[ data.message ] );
			},
			error: function( message ) {
				tp.save_changes.after_saving_dialog( 'error', message );
				//alert( tablepress_strings.save_changes_error );
			},
			after_saving_dialog: function( type, message ) {
				if ( 'undefined' == typeof message )
					message = '';
				else
					message = ' ' + message;
				$( '.animation-saving' )
					.after( '<span class="save-changes-' + type + '">' + tablepress_strings['save_changes_' + type] + message + '</span>' )
					.remove();
				$( '.save-changes-' + type ).delay( 2000 ).fadeOut( 2000, function() { $(this).remove(); } );
				$( '.save-changes-button' ).prop( 'disabled', false );
				$( 'body' ).removeClass( 'wait' );
			}
		},
		init: function() {
			var callbacks = {
				'click': {
					'#rows-insert':			tp.rows.insert,
					'#columns-insert':		tp.columns.insert,
					'#rows-remove':			tp.rows.remove,
					'#columns-remove':		tp.columns.remove,
					'#rows-hide':			tp.rows.hide,
					'#columns-hide':		tp.columns.hide,
					'#rows-unhide':			tp.rows.unhide,
					'#columns-unhide':		tp.columns.unhide,
					'#rows-append':			tp.rows.append,
					'#columns-append':		tp.columns.append,
					'#link-add':			tp.content.link.add,
					'#image-add':			tp.content.image.add,
					'#span-add-rowspan':	function() { tp.content.span.add( '#rowspan#' ); },
					'#span-add-colspan':	function() { tp.content.span.add( '#colspan#' ); },
					'.show-preview-button':	tp.table.preview.trigger,
					'.save-changes-button':	tp.save_changes.trigger
				},
				'keyup': {
					'#table-id':			tp.check.table_id
				},
				'change': {
					'#option-table-head':	tp.table.change_table_head,
					'#option-table-foot':	tp.table.change_table_foot
				},
				'blur': {
					'#table-id':			tp.table.change_id	// onchange would not recognize changed values from tp.check.table_id
				}
			},
			$table = $( '#edit-form-body' );

			$.each( callbacks, function( event, event_callbacks ) {
				$.each( event_callbacks, function( selector, callback ) {
					$( selector ).on( event, callback );
				} );
			} );

			$( window ).on( 'beforeunload', tp.check.changes_saved );

			$table.one( 'change', 'textarea', tp.table.set_table_changed ); // just once is enough, will be reset after saving

			if ( tablepress_options.cells_advanced_editor ) {
				$table.on( 'click', 'textarea', tp.cells.advanced_editor.keyopen );
				$( '#advanced-editor-open' ).on( 'click', tp.cells.advanced_editor.buttonopen );
				$( '#advanced-editor-confirm' ).on( 'click', tp.cells.advanced_editor.save );
				$( '#advanced-editor-cancel' ).on( 'click', tp.cells.advanced_editor.close );
			}

			if ( tablepress_options.cells_auto_grow )
				$table.on( 'focus', 'textarea', tp.cells.autogrow );

			$( '#edit-form-body' ).on( 'click', 'input:checkbox', { parent: '#edit-form-body' }, tp.cells.checkboxes.multi_select );
			$( '#edit-form-foot' ).on( 'click', 'input:checkbox', { parent: '#edit-form-foot' }, tp.cells.checkboxes.multi_select );

			$( '#edit-form-head' ).on( 'click', '.sort-control', tp.rows.sort );

			$( '#tablepress_edit-table-information' ).on( 'change', 'input, textarea', tp.table.set_table_changed );

			$table.sortable( {
				axis: 'y',
				containment: $( '#edit-form' ), // to get better behavior when dragging before/after the first/last row
				forceHelperSize: true, // necessary?
				handle: '.move-handle',
				start: tp.rows.move.start,
				change: tp.rows.move.change,
				stop: tp.rows.move.stop,
				update: tp.reindex
			} ); // disableSelection() prohibits selection of text in textareas via keyboard

			$( '#edit-form-head' ).sortable( {
				axis: 'x',
				items: '.head',
				containment: 'parent',
				forceHelperSize: true, // necessary?
				helper: 'clone',
				handle: '.move-handle',
				start: tp.columns.move.start,
				stop: tp.columns.move.stop,
				change: tp.columns.move.change,
				sort: tp.columns.move.sort
			} ).disableSelection();

		}
	};

	// do allow wide tables to scroll sideways
	$( '#wpbody-content' ).css( 'overflow-x', 'scroll' );

	tp.init();

} );

/**
 * On click on "Insert into Post" in the Media Library, this function is called by WordPress
 *
 * @see media-upload.dev.js
 *
 * @since 1.0.0
 *
 * @param string new_html HTML code that gets appended to the cell content of the cell that has been marked as active editor
 */
function send_to_editor( new_html ) {
	wpActiveEditor.value += new_html;
	wpActiveEditor.selectionStart = wpActiveEditor.selectionEnd = wpActiveEditor.value.length;
	jQuery( wpActiveEditor ).focus();
	// tp.table.set_changed(); // @TODO: make this work by making the object available
	tb_remove();
}