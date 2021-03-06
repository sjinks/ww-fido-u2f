/** global u2f, wwU2F, ajaxurl */
(function() {
	var $, form, spinner, list, btn_submit, key_name, hint;

	function showError(msg, where)
	{
		var node = document.querySelector('#' + where + ' + .notice');
		node && node.parentNode.removeChild(node);
		$(where).insertAdjacentHTML('afterend', '<div class="notice notice-error inline"><p>' + msg + '</p></div>');
	}

	function showSuccess(msg, where)
	{
		var node = document.querySelector('#' + where + ' + .notice');
		node && node.parentNode.removeChild(node);
		$(where).insertAdjacentHTML('afterend', '<div class="notice notice-success inline"><p>' + msg + '</p></div>');
	}

	function hideMessages()
	{
		var node = document.querySelector('#new-key + .notice');
		node && node.parentNode.removeChild(node);
		node = document.querySelector('#registered-keys + .notice');
		node && node.parentNode.removeChild(node);
	}

	function showSpinner(show)
	{
		if (show) {
			spinner.classList.add('is-active');
			btn_submit.setAttribute('disabled', '');
			hint.removeAttribute('hidden');
		}
		else {
			spinner.classList.remove('is-active');
			btn_submit.removeAttribute('disabled');
			hint.setAttribute('hidden', '');
		}
	}

	function haveNewItems()
	{
		var noitems = list.querySelector('tr.no-items')
		if (noitems) {
			noitems.parentNode.removeChild(noitems);
		}
	}

	function registerCompleted()
	{
		showSpinner(false);
		if (null === this.response || this.status !== 200) {
			showError(wwU2F.serverError, 'new-key');
			return;
		}

		if (this.response.ok) {
			haveNewItems();
			list.insertAdjacentHTML('beforeend', this.response.row);
			showSuccess(this.response.message, 'new-key');

			wwU2F.sigs     = this.response.sigs;
			wwU2F.request  = this.response.request;
			key_name.value = '';
		}
		else {
			showError(this.response.message, 'new-key');
		}
	}

	function doRegister()
	{
		hideMessages();

		var request = [{
			version: wwU2F.request.version,
			challenge: wwU2F.request.challenge
		}];

		u2f.register(wwU2F.request.appId, request, wwU2F.sigs, function(data) {
			if (data.errorCode) {
				var err = wwU2F.errors[data.errorCode] || wwU2F.errors[1];
				showError(err, 'new-key');
				showSpinner(false);
				return;
			}

			var name = encodeURIComponent(key_name.value);
			var req  = new XMLHttpRequest();
			req.addEventListener('load', registerCompleted);
			req.open('POST', ajaxurl);
			req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			req.responseType = 'json';
			req.send('action=wwu2f_register&data=' + encodeURIComponent(JSON.stringify(data)) + '&name=' + name);
		});
	}

	function killRow(target)
	{
		while (target !== null && target.tagName.toUpperCase() !== 'TR') {
			target = target.parentNode;
		}

		target && target.parentNode.removeChild(target);
	}

	function maybeNoItems()
	{
		if (!list.getElementsByTagName('tr').length) {
			var tpl = $('tpl-empty').textContent;
			list.insertAdjacentHTML('beforeend', tpl);
		}
	}

	function revokeCompleted()
	{
		var spinner = this.tgt.querySelector('.spinner');
		spinner.classList.remove('is-active');

		if (null === this.response || this.status !== 200) {
			showError(wwU2F.serverError, 'registered-keys');
			return;
		}

		if (this.response.ok) {
			showSuccess(this.response.message, 'registered-keys');
			killRow(this.tgt);
			maybeNoItems();
			wwU2F.sigs = this.response.sigs;
		}
		else {
			showError(this.response.message, 'registered-keys');
		}
	}

	function magic()
	{
		$          = document.getElementById.bind(document);
		form       = $('new-key-form');
		spinner    = form.querySelector('.spinner');
		list       = $('the-list');
		btn_submit = $('submit-button');
		key_name   = $('key-name');
		hint       = $('hint');

		form.addEventListener('submit', function(e) {
			e.preventDefault();
			if (form.reportValidity()) {
				showSpinner(true);
				doRegister();
			}
		});

		document.querySelector('table.widefat > tbody').addEventListener('click', function(e) {
			var target = e.target;
			while (target !== null && (!target.tagName || target.tagName.toUpperCase() !== 'BUTTON' || target.className.indexOf('revoke-button') === -1)) {
				target = target.parentNode;
			}

			if (target && confirm(wwU2F.revconfirm)) {
				var handle = target.dataset.handle;
				var nonce  = target.dataset.nonce;
				var req    = new XMLHttpRequest();
				req.tgt    = target;
				req.addEventListener('load', revokeCompleted);

				var spinner = target.querySelector('.spinner');
				spinner.style.float = 'none';
				spinner.style.margin = 0;
				spinner.classList.add('is-active');
				hideMessages();

				req.open('POST', ajaxurl);
				req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				req.responseType = 'json';
				req.send(
					  'action=wwu2f_revoke'
					+ '&handle=' + encodeURIComponent(handle) 
					+ '&_wpnonce=' + nonce
				);
			}
		});

		if (typeof u2f === 'undefined' || !u2f.register) {
			showError(wwU2F.noSupport, 'new-key');
		}
	}

	function callback() {
		if (typeof u2f === 'undefined' || !u2f.register) {
			var s = document.createElement('script');
			s.setAttribute('src', wwU2F.u2f_api);
			s.setAttribute('async', '');
			s.addEventListener('load', magic);
			s.addEventListener('error', magic);
			document.head.appendChild(s);
		}
		else {
			magic();
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', callback);
	}
	else {
		callback();
	}
})();
