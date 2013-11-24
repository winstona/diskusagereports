/*
 * Disk Usage Reports
 * http://diskusagereports.com/
 *
 * Copyright (c) 2013 André Mekkawi <diskusage@andremekkawi.com>
 * This source file is subject to the MIT license in the file LICENSE.txt.
 * The license is also available at http://diskusagereports.com/license.html
 */
define([
	'backbone',
	'layout',
	'underscore',
	'text!templates/view.title.html',
	'i18n!nls/report'
], function(Backbone, Layout, _, template, lang){

	return Layout.extend({

		template: _.template(template),
		el: false,

		serialize: function() {
			return {
				lang: lang,
				reportTitle: this.model && this.model.get("name") || null
			};
		},

		addListeners: function() {
			if (this.model)
				this.listenTo(this.model, "change:name", this.render);

			return this;
		}
	});

});