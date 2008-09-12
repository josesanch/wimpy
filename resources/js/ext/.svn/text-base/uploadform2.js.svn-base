// Create user extension namespace
Ext.namespace('Ext.ux');

Ext.ux.UploadForm2 = function(ct, config) {

	// setup autoCreate, container and el
	var autoCreate =
		 config.autoCreate === true
			? this.defaultAutoCreate
			: typeof config.autoCreate === 'object'
				? config.autoCreate
				: typeof config.autoCreate === "undefined" ? this.defaultAutoCreate : false;
	var el = autoCreate ? Ext.DomHelper.append(ct, autoCreate) : ct;
	ct = Ext.get(ct);
	this.container = ct;

	// call parent constructor
	Ext.ux.UploadForm2.superclass.constructor.call(this, el, config);

	// create "storage" for inputs
	this.inputs = new Ext.util.MixedCollection();

 	// create layer if the form should float
	this.createLayer();

	// setup icons for 1.x API compatability
	this.createTextAndIcons(config);

	// create hidden fields containing upload progress IDs + max file size
	this.createHiddenFields();

	// create input buttons for interaction with widget
	this.createButtons();

	// create the progress info box
	this.createProgressInfo();

	// create the initial upload file input box
	this.createUploadInput();


	// event handlers for form
	this.on({
		'actioncomplete': {
			scope: this,
			fn: this.onSuccess
		},
		'actionfailed': {
			scope: this,
			fn: this.onFailure
		}
	});

	// add events
	this.addEvents(
		/**
		 * Fires when a file is added to the queue
		 * @event fileadded
		 * @param {UploadForm} this
		 * @param {String} auto-generated file id
		*/
		'fileadded',
		/**
		 * Fires when a file is removed from the queue
		 * @event fileremoved
		 * @param {UploadForm} this
		 * @param {String} auto-generated file id
		 */
		'fileremoved',
		/**
		 * Fires when the queue is cleared
		 * @event clearqueue
		 * @param {UploadForm} this
		 */
		'clearqueue',
		/**
		 * Fires when upload starts
		 * @event startupload
		 * @param {UploadForm} this
		 */
		'startupload',
		/**
		 * Fires when upload stops
		 * @event stopupload
		 * @param {UploadForm} this
		 */
		'stopupload',
		/**
		 * Fires on a progress update
		 * @event progressupdate
		 * @param {UploadForm} this
		 * @param {Object} object with progress values
		 * @param {Floag} value 0 - 1 for progress bar
		 */
		'progressupdate'
	);

};


// extend BasicForm
Ext.extend(Ext.ux.UploadForm2, Ext.form.BasicForm, {

	// default widget values
	autoCreate: true,
	maxFileSize: 0,
	maxNameLength: 24,
	buttonWidth: 78,
	width: 200,

	//fileCls : 'file',

	// text
	textConfig: {
		add: 'Add',
		clearAll: 'Clear All',
		pgEta: 'Rem. time',
		pgSize: 'Size/Total',
		pgSpeedAvg: 'Avg. Speed',
		pgSpeed: 'Speed',
		stop: 'Stop',
		uploadProgress: 'Upload Progress',
		upload: 'Upload'
	},


	// other stuff
	defaultAutoCreate: {
		tag: 'form',
		enctype: 'multipart/form-data'
	},
	progressZeroValues: {
		bytes_uploaded: 0,
		bytes_total: 0,
		speed_last: 0,
		speed_average: 0,
		est_sec: 0
	},
	progressDefaultMap: {
		time_start: 'time_start',
		time_last: 'time_last',
		speed_average: 'speed_average',
		speed_last: 'speed_last',
		bytes_uploaded: 'bytes_uploaded',
		bytes_total: 'bytes_total',
		files_uploaded: 'files_uploaded',
		est_sec: 'est_sec'
	},
	iconPath: 'images/icons',
	iconConfig: {
		add: 'add.png',
		remove: 'delete.png',
		upload: 'arrow_up.png',
		stop: 'control_stop.png',
		clear: 'cross.png',
		success: 'accept.png',
		failure: 'exclamation.png',
		uploaded: 'tick.png'
	},

	/**
	 * Creates a layer for the form to float on
	 *
	 */
	createLayer: function() {
		if(this.floating === true)
		{
			var wrap, showShadow;
			wrap = this.container.wrap({
				tag:'div',
				cls:'x-uf-layer'
			});
			this.layer = new Ext.Layer({
				shadow:'sides'
			}, wrap);
			this.layer.setWidth(this.width);
			this.container.addClass('x-uf-layer-form-ct');

			// event handlers
			showShadow = function()
			{
				if(this.layer && this.layer.isVisible())
				{
					this.layer.shadow.show(this.layer.dom);
				}
			}
			this.on({
				fileadded: {
					scope: this,
					fn: showShadow
				},
				fileremoved: {
					scope:this,
					fn: showShadow
				},
				clearqueue: {
					scope:this,
					fn: showShadow
				}
			});
		}
	},

	/**
	 * Creates icons for 1.x API compatability
	 *
	 */
	createTextAndIcons: function(config) {

		this.iconPath = config && config.iconPath ? config.iconPath : this.iconPath;
		this.icons = {};
		for (icon in this.iconConfig)
		{
			var iconName = icon + 'Icon';
			this.icons[icon] = this.iconPath + '/' + ((config && config[iconName]) ? config[iconName] : this.iconConfig[icon]);

		}

		this.text = {};
		for (string in this.textConfig)
		{
			var stringText = string + 'Text';
			this.text[string] = (config && config[stringText]) ? config[stringText] : this.textConfig[string];
		}
	},

	/**
	 * Creats hidden fields with MAX_FILE_SIZE and upload ID if we need progress updates
	 *
	 */
	createHiddenFields: function() {
		// create hidden field for max file size
		if (this.maxFileSize)
		{
			Ext.DomHelper.append(this.el, {
				tag: 'input',
				type: 'hidden',
				name: 'MAX_FILE_SIZE',
				value: this.maxFileSize
			});
		}

		// create hidden field for upload progress id
		if(this.pgCfg && this.pgCfg.uploadIdName)
		{
			this.uploadId = Ext.DomHelper.append(this.el, {
				tag:'input',
				type:'hidden',
				name: this.pgCfg.uploadIdName,
				value: this.pgCfg.uploadIdValue
			});
		}
	},

	/**
	 * Creates buttons for interaction with the widget
	 *
	 */
	createButtons: function() {
		var ct = Ext.DomHelper.append(this.el, {
			tag: 'div',
			cls: 'x-uf-buttons-ct',
			children: [{
				tag: 'div',
				cls: 'x-uf-input-ct',
				children: [{
					tag: 'div',
					cls: 'x-uf-bbtn-ct'
				},{
					tag: 'div',
					cls: 'x-uf-input-wrap'
				}]
			},{
				tag: 'div',
				cls: 'x-uf-wait'
			},{
				tag: 'div',
				cls: 'x-uf-ubtn-ct'
			},{
				tag: 'div',
				cls: 'x-uf-cbtn-ct'
			}]
		}, true);

		this.buttonsWrap = ct;
		this.inputWrap = ct.select('div.x-uf-input-wrap').item(0);
		this.addBtnCt = ct.select('div.x-uf-input-ct').item(0);

		// browse button
		this.bbtnCt = ct.select('div.x-uf-bbtn-ct').item(0);
		this.browseBtn = new Ext.Button({
			renderTo: this.bbtnCt,
			text: this.text['add'],
			cls: 'x-btn-text-icon',
			icon: this.icons['add'],
			minWidth: this.buttonWidth
		});

		// upload button
		this.ubtnCt = ct.select('div.x-uf-ubtn-ct').item(0);
		this.uploadBtn = new Ext.Button({
			renderTo: this.ubtnCt,
			icon: this.icons['upload'],
			cls: 'x-btn-icon',
			tooltip: this.text['upload'],
			scope: this,
			handler: this.startUpload
		});

		// clear button
		this.cbtnCt = ct.select('div.x-uf-cbtn-ct').item(0);
		this.clearBtn = new Ext.Button({
			renderTo: this.cbtnCt,
			icon: this.icons['clear'],
			cls: 'x-btn-icon',
			tooltip: this.text['clearAll'],
			scope: this,
			handler: this.clearQueue
		});

		// get the wait icon element
		this.waitIcon = ct.select('div.x-uf-wait').item(0);
	},

	/**
	 * Creates the progress info box
	 *
	 */
	createProgressInfo: function() {
		if (this.pgCfg && this.pgCfg.progressBar === true)
		{
			var wrap = Ext.DomHelper.append(this.el, {
				tag: 'div',
				cls: 'x-uf-progress-wrap',
				children: [{
					tag: 'div',
					cls: 'x-uf-progress',
					children: [{
						tag: 'div',
						cls: 'x-uf-progress-bar'
					}]
				}]
			}, true);
			this.progressBar = wrap.select('div.x-uf-progress-bar').item(0);
		}

		if (this.pgCfg)
		{
			var pgInfoCreate = {
				tag: 'div',
				cls: 'x-uf-pginfo-ct'
			};
			var pgTargetPos = this.pgCfg.progressTarget;
			pgTargetPos = (pgTargetPos === 'above' && !wrap) ? 'under' : pgTargetPos;

			if (this.pgCfg && this.pgCfg.progressTarget && !this.progressTarget)
			{
				switch (pgTargetPos)
				{
					case 'under':
					case 'below':
						this.progressTarget = Ext.DomHelper.append(this.el, pgInfoCreate, true);
						break;
					case 'above':
						this.progressTarget = Ext.DomHelper.insertBefore(this.el, pgInfoCreate, true);
						break;
				}
			}

			// reset progress info
			this.updateProgress(0);
		}
	},

	/**
	 * Creates a fresh file upload input box
	 *
	 */
	createUploadInput: function() {
		var id = Ext.id();
		var inp = Ext.DomHelper.append(this.inputWrap, {
			tag: 'input',
			type: 'file',
			cls: 'x-uf-input',
			size: 1,
			id: id,
			name: id
		}, true);
		inp.on('change', this.onFileAdded, this);
		this.inputs.add(inp);
		this.fireEvent('fileadded', this, id);
		return inp;
	},

	/**
	 * File add event handler
	 * @param {Event} e
	 * @param {Element} inp File upload input box
	 */
	onFileAdded: function(e, inp) {
		this.inputs.each(function(i){
			i.setDisplayed(false);
		});

		if (!this.table)
		{
			this.table = Ext.DomHelper.append(this.el, {
				tag: 'table',
				cls: 'x-uf-table',
				children: [{
					tag: 'tbody'
				}]
			}, true);
			this.tbody = this.table.select('tbody').item(0);

			this.table.on({
				'click': {
					scope: this,
					fn: this.onDeleteFile,
					delegate: 'a'
				}
			});
		}
		var inp = this.inputs.itemAt(this.inputs.getCount() - 1);

		inp.un('change', this.onFileAdded, this);

		this.appendRow(inp);

		this.createUploadInput();
	},

	/**
	 * File delete event handler
	 * @param {Event} e
	 * @param {Element} target Target input clicked
	 */
	onDeleteFile: function(e, target) {
		this.removeFile(target.id.substr(2));
	},

	/**
	 * Removes all files from the queue. Individual remove events are surpressed
	 *
	 */
	clearQueue: function() {
		if (this.uploading)
		{
			return true;
		}

		this.waitIcon.setDisplayed('none');
		this.updateProgress(0);
		this.inputs.each(function(inp) {
			if (!inp.isVisible())
			{
				this.removeFile(inp.id, true);
			}
		}, this);

		this.fireEvent('clearqueue', this);
	},

	/**
	 * Removes a file from the queue
	 * @param {String} id ID of the file to remove (ID is autogenerated when it is added)
	 * @param {Boolean} suppressEvent Set to true to not fire 'fileremoved' event
	 */
	removeFile: function(id, suppressEvent) {
		if (this.uploading)
		{
			return;
		}

		var inp = this.inputs.get(id);
		if (inp && inp.row)
		{
			inp.row.remove();
		}
		if (inp)
		{
			inp.remove();
		}
		this.inputs.removeKey(id);
		if (suppressEvent !== true)
		{
			this.fireEvent('fileremoved', this, id);
		}
	},

	/**
	 * Appends a row to the queue table to display the file.
	 * @param {Element} inp File input element for which to display file
	 */
	appendRow: function(inp) {
		var filename = inp.getValue();
		var obj = {
			id: inp.id,
			fileCls: this.getFileCls(filename),
			fileName: Ext.util.Format.ellipsis(filename.split(/[\/\\]/).pop(), this.maxNameLength),
			fileQtip: filename
		}

		var template = new Ext.Template(
			'<tr id="r-{id}">',
				'<td class="x-unselectable {fileCls} x-tree-node-leaf">',
					'<img class="x-tree-node-icon" src="' + Ext.BLANK_IMAGE_URL + '" />',
					'<span class="x-uf-filename" unselectable="on" qtip="{fileQtip}">{fileName}</span>',
				'</td>',
				'<td id="m-{id}" class="x-uf-filedelete">',
					'<a id="d-{id}" href="#"><img src="' + this.icons['remove'] + '" /></a>',
				'</td>',
			'</tr>'
		);

		inp.row = template.append(this.tbody, obj, true);
	},

	/**
	 * Updates upload progress information. Takes in to account existence of progressBar and progressTarget.
	 * @param {Integer/Object} value 0 = clear, 1 = done, object with raw progress values
	 */
	updateProgress: function(value) {

		var obj;

		// reset progress
		if (value === 0)
		{
			obj = Ext.apply({}, this.progressZeroValues);
		}

		if (value === 1 && this.lastPgObj)
		{
			obj = this.lastPgObj;
			obj.bytes_uploaded = obj.bytes_total;
			obj.est_sec = 0;
		}

		if (typeof value === 'object')
		{
			obj = this.remapProgress(value);
			value = obj.bytes_total ? obj.bytes_uploaded / obj.bytes_total : 0;
		}

		this.lastPgObj = obj;

		if (obj.files_uploaded > 0)
		{
			var i = 1;
			this.inputs.each(function(inp) {
				if (i <= obj.files_uploaded)
				{
					var iconTarget = Ext.get('m-' + inp.id);
					if (iconTarget)
					{
						iconTarget.update('<img src="' + this.icons['uploaded'] + '" />');
					}
				}
				i++;
			}, this);
		}

		if (this.progressBar)
		{
			var pp = Ext.get(this.progressBar.dom.parentNode);
			this.progressBar.setWidth(Math.floor(value * pp.dom.offsetWidth));
		}

		if (this.progressTarget)
		{
			this.getProgressTemplate().overwrite(this.progressTarget, this.formatProgress(obj));
		}
		else if (this.pgCfg && this.pgCfg.progressTarget === 'qtip' && this.progressBar)
		{
			Ext.QuickTips.init();
			Ext.QuickTips.register({
				target: this.progressBar,
				title: this.text['uploadProgress'],
				text: this.getProgressTemplate().apply(this.formatProgress(obj)),
				width: 160,
				autoHide: true
			});
		}

		this.fireEvent('progressupdate', this, obj, value);

	},

	/**
	 * Remaps raw progress values to internal progress object
	 * @param {Object} obj Raw progress object as received from server
	 * @return {Object} Object with progress values
	 */
	remapProgress: function(obj) {
		obj = obj || {};

		var map = this.pgCfg.map || this.progressDefaultMap;

		var p, obj_new = {};

		for (p in map)
		{
			obj_new[p] = obj[map[p]] || '';
		}

		return obj_new;
	},

	/**
	 * Creates a template to display progress info
	 * @return {Template}
	 */
	getProgressTemplate: function() {
		var tpl = new Ext.Template(
			'<table class="x-uf-pginfo-table">',
				'<tbody>',
					'<tr>',
						'<td class="x-uf-pginfo-label">' + this.text['pgSize'] + ':</td>',
						'<td class="x-uf-pginfo-value">{bytes_uploaded}/{bytes_total}</td>',
					'</tr>',
					'<tr>',
						'<td class="x-uf-pginfo-label">' + this.text['pgSpeed'] + ':</td>',
						'<td class="x-uf-pginfo-value">{speed_last}</td>',
					'</tr>',
					'<tr>',
						'<td class="x-uf-pginfo-label">' + this.text['pgSpeedAvg'] + ':</td>',
						'<td class="x-uf-pginfo-value">{speed_average}</td>',
					'</tr>',
					'<tr>',
						'<td class="x-uf-pginfo-label">' + this.text['pgEta'] + ':</td>',
						'<td class="x-uf-pginfo-value">{est_sec}</td>',
					'</tr>',
				'</tbody>',
			'</table>'
		);

		tpl.compile();
		return tpl;
	},

	/**
	 * Formats progress object before it us used by the template
	 * @param {Object} obj Object containing progress values
	 * @return {Object} Object with formatted progress values
	 */
	formatProgress: function(obj) {
		var robj = {};

		var data = this.formatBytes(obj.bytes_uploaded);
		robj.bytes_uploaded = data[0]  + ' ' + data[1];

		var data = this.formatBytes(obj.bytes_total);
		robj.bytes_total = data[0] + ' ' + data[1];

		var data = this.formatBytes(obj.speed_last);
		robj.speed_last = data[0] + ' ' + data[1] + '/s';

		var data = this.formatBytes(obj.speed_average);
		robj.speed_average = data[0] + ' ' + data[1] + '/s';

		var time = this.formatTime(obj.est_sec);

		robj.est_sec = (time === '00:00' && this.progressRequestCount >= 1 && !this.uploading) ? 'Done' : time;

		return robj;
	},

	/**
	 * Formats raw bytes into kB/mB/GB/TB
	 * @param {Integer} bytes
	 * @return {Array} [value, unit]
	 */
	formatBytes: function(bytes) {
		if(isNaN(bytes))
		{
			return ['', ''];
		}

		var unit, val;

		if(bytes < 999)
		{
			unit = 'B';
			val = (!bytes && this.progressRequestCount >= 1) ? '~' : bytes;
		}
		else if(bytes < 999999)
		{
			unit = 'kB';
			val = Math.round(bytes/1000);
		}
		else if(bytes < 999999999)
		{
			unit = 'MB';
			val = Math.round(bytes/100000) / 10;
		}
		else if(bytes < 999999999999)
		{
			unit = 'GB';
			val = Math.round(bytes/100000000) / 10;
		}
		else
		{
			unit = 'TB';
			val = Math.round(bytes/100000000000) / 10;
		}

		return [val, unit];
	},

	/**
	 * Formats time to hh:mm:ss omiting hh: if zero
	 * @param {Integer} seconds Seconds to format
	 * @return {String} Formatted time
	 */
	formatTime: function(seconds) {
		var s = m = h = 0;

		if(seconds > 3599)
		{
			h = parseInt(seconds/3600);
			seconds -= h * 3600;
		}
		if(seconds > 59) {
			m = parseInt(seconds/60);
			seconds -= m * 60;
		}

		m = String.leftPad(m, 2, 0);
		h = String.leftPad(h, 2, 0);
		s = String.leftPad(seconds, 2, 0);

		return ("00" !== h ? h + ':' : '') + m + ':' + s;
	},

	/**
	 * Returns a file class based on the extension
	 * @param {String} name Filename to get class for
	 */
	getFileCls: function(name) {
		var atmp = name.split('.');
		if(1 === atmp.length)
		{
			return this.fileCls;
		}
		else
		{
			return this.fileCls + '-' + atmp.pop();
		}
	},

	/**
	 * Starts the upload process
	 *
	 */
	startUpload: function() {

		if (this.inputs.getCount() < 2)
		{
			return;
		}

		this.progressRequestCount = 0;

		this.updateProgress(0);

		if (this.uploading)
		{
			this.stopUpload(true);
			return;
		}

		this.uploading = true;
		this.waitIcon.setDisplayed('block');
		this.startProgress();
		this.setDisabled(true);
		this.updateUploadBtn();
		this.submit({
			url: this.url
		});
		this.findIframe();
		this.fireEvent('startupload', this);
	},

	/**
	 * Stops the upload process (cancels if mid upload)
	 * @param {Boolea} reset Whether to reset the progress to zero or leave as done
	 */
	stopUpload: function(reset) {
		if (this.iframe)
		{
			try
			{
				this.iframe.dom.contentWindow.stop();
				this.removeIframe.defer(250, this);
			}
			catch (e) {}
		}

		this.uploading = false;
		this.setDisabled(false);
		this.waitIcon.setDisplayed('none');
		this.stopProgress();

		// this flag is used to know whether or not to reset the progress to zero
		if (reset)
		{
			// reset the progress back to zero (only used if we cancel the upload while it is going on)
			this.updateProgress(0);
		}
		else
		{
			this.updateProgress(1);
		}

		this.updateUploadBtn();
		this.progressRequestCount = 0;
		this.fireEvent('stopupload', this);
	},

	/**
	 * Starts querying the server for progress info (no more often than the interval).
	 *
	 */
	startProgress: function() {
		var p = this.pgCfg;
		if (p)
		{
			if (this.uploadId)
			{
				if (p.uploadIdValue === 'auto')
				{
					this.uploadId.value = parseInt(Math.random() * 1e10);
				}
				p.options.params = p.options.params || {};
				p.options.params[p.uploadIdName] = this.uploadId.value;
			}
			p.options.scope = p.options.scope || this;
			p.options.callback = p.options.callback || this.defaultProgressCallback;
			this.timerId = setInterval(this.requestProgress.createDelegate(this), p.interval || 1000);
			this.requestProgress();
		}
	},

	/**
	 * Stops any further progress updates from happening
	 *
	 */
	stopProgress: function() {
		if (this.timerId)
		{
			clearInterval(this.timerId);
		}
	},

	/**
	 * Processes progress info received from server. Callback specified in this.pgCfg takes precedence
	 * @param {Object} options
	 * @param {Boolean} bSuccess
	 * @param {Object} response Server response
	 */
	defaultProgressCallback: function(options, bSuccess, response) {
		this.activeProgressRequest = false;

		if (this.processingProgress)
		{
			return;
		}
		this.processingProgress = true;

		var obj;
		try
		{
			obj = Ext.decode(response.responseText) || {};
		}
		catch (e) {}

		if (obj && obj.success === true && this.uploading)
		{
			this.updateProgress(obj);
			this.pgErrors = 0;
		}
		else
		{
			this.pgErrors = this.pgErrors || 0;
			this.pgErrors++;
			if ((this.pgCfg.maxPgErrors || 10) < this.pgErrors)
			{
				this.stopProgress();
			}
		}

		this.processingProgress = false;
	},

	/**
	 * Creates an ajax request to retreive progress information from the server.
	 * Fixed to ensure that previous progress query is done before starting a new one.
	 */
	requestProgress: function() {
		if (!this.activeProgressRequest)
		{
			this.progressRequestCount++;
			var conn = new Ext.data.Connection().request(this.pgCfg.options);
		}
		this.activeProgressRequest = true;
	},

	/**
	 * Processes both success and failure responses from server
	 * @param {Form} form Form than has been submitted
	 * @param {Action} action Action that has been executed
	 */
	processResponse: function(form, action) {
		this.stopUpload();
		var obj = action.response.responseText ? Ext.decode(action.response.responseText) : {};
		this.inputs.each(function(inp) {
			var msgTarget = Ext.get('m-' + inp.id);
			if (!obj.errors || !obj.errors[inp.id])
			{
				if (msgTarget)
				{
					msgTarget.update('<img src="' + this.icons['success'] + '" />');
				}
				if (inp.getValue() !== '')
				{
					inp.markRemove = true;
				}
			}
			else if (obj.errors[inp.id])
			{
				if (msgTarget)
				{
					msgTarget.update('<img src="' + this.icons['failure'] + '">');
					/*Ext.QuickTips.init();
					Ext.QuickTips.register({
						target: msgTarget.select('img'),
						title: 'test'+this.text['serverError'],
						text: obj.errors[inp.id],
						width: 160,
						autoHide: true
					});*/
				}
			}
		}, this);

		this.inputs.each(function(inp) {
			if (inp.markRemove === true)
			{
				this.removeFile(inp.id);
			}
		}, this);
	},

	/**
	 * Finds the hidden iframe created by Ext that is the form submit target
	 *
	 */
	findIframe: function() {
		this.iframe = Ext.get(document.body).select('iframe.x-hidden').item(0);
		if (this.uploading && !this.iframe)
		{
			this.findIframe.defer(200, this);
		}
	},

	/**
	 * Removes iframe created by Ext if we cancel mid upload
	 *
	 */
	removeIframe: function() {
		if (this.iframe)
		{
			this.iframe.remove();
		}
	},

	/**
	 * Success form submit event handler
	 * @param {Ext.ux.UploadForm2} this
	 * @param {Ext.form.Action} action Action object
	 */
	onSuccess: function(form, action) {
		this.processResponse(form, action);
	},

	/**
	 * Failure form submit event handler
	 * @param {Ext.ux.UploadForm2} this
	 * @param {Ext.form.Action} action Action object
	 */
	onFailure: function(form, action) {
		this.processResponse(form, action);
	},

	/**
	 * Disables/Enables the whole form by masking/unmasking it
	 * @param {Boolean} disable true to disable, false to enable
	 * @param {Boolean} alsoUpload true to disable upload button
	 */
	setDisabled: function(disable, alsoUpload) {
		if (disable)
		{
			this.addBtnCt.mask();
			if (alsoUpload === true)
			{
				this.ubtnCt.mask();
			}
			this.cbtnCt.mask();
		}
		else
		{
			this.addBtnCt.unmask();
			this.ubtnCt.unmask();
			this.cbtnCt.unmask();
		}
	},

	/**
	 * Displays upload button or stop button depending on uploading state
	 *
	 */
	updateUploadBtn: function() {
		this.uploadBtn.setIcon(this.uploading ? this.icons['stop'] : this.icons['upload']);
		this.uploadBtn.setQtip(this.uploading ? this.text['stop'] : this.text['upload']);
	},

	/**
	 * Shows the form at a position, if floating
	 * @param {Array} xy position
	 * @param {Boolean/Element} animEl animation element
	 */
	showAt: function(xy, animEl) {
		if (this.layer)
		{
			this.layer.setXY(xy);
			this.layer.show(animEl);
		}
	},

	/**
	 * Hides the form (only if floating)
	 * @param {Boolean/Element} animEl animation element
	 */
	hide: function(animEl) {
		if (this.layer)
		{
			this.layer.hide(animEl);
		}
	}


});


// Override Ext.Button to add some useful button methods: setIcon and setQtip
Ext.override(Ext.Button, {
	setIcon: function(icon) {
		this.icon = icon;
		this.el.select('button').item(0).setStyle('background-image', 'url(' + this.icon + ')');
	},
	setQtip: function(qtip) {
		if(qtip) {
			this.tooltip = qtip;
			if(typeof this.tooltip == 'object')
			{
				Ext.QuickTips.tips(Ext.apply({
					target: btnEl.id
				}, this.tooltip));
			}
			else
			{
				this.el.select('button').item(0).dom[this.tooltipType] = this.tooltip;
			}
		}
	}
});
