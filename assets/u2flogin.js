/** global u2f, wwU2F */
(function() {
	var $, form, submit, pbar, lerr;

	function showError(msg)
	{
		while (lerr.firstChild) {
			lerr.removeChild(lerr.firstChild);
		}

		lerr.insertAdjacentHTML('afterbegin', msg);
		lerr.removeAttribute('hidden');
	}

	function requestCompleted()
	{
		if (null === this.response || this.status !== 200) {
			showError(wwU2F.serverError);
			submit.removeAttribute('hidden');
			pbar.setAttribute('hidden', '');
			return;
		}

		if (this.response.ok) {
			if (this.response.redirect) {
				window.location.href = this.response.redirect;
			}
			else if (this.response.message) {
				document.querySelector('.progressbar-container').style.display = 'none';
				$('interact').innerHTML = this.response.message;
			}
		}
		else {
			showError(this.response.message);
			submit.removeAttribute('hidden');
			pbar.setAttribute('hidden', '');
		}
	}

	function signCallback(data)
	{
		if (data.errorCode) {
			var err = wwU2F.errors[data.errorCode] || wwU2F.errors[1];
			showError(err);
			submit.removeAttribute('hidden');
			pbar.setAttribute('hidden', '');
		}
		else {
			var remember      = encodeURIComponent($('rememberme').value);
			var redirect      = encodeURIComponent($('redirect_to').value);
			var user_id       = encodeURIComponent($('user_id').value);
			var interim_login = encodeURIComponent($('interim_login').value);
			var req = new XMLHttpRequest();
			req.addEventListener('load', requestCompleted);
			req.open('POST', wwU2F.ajax_url);
			req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			req.responseType = 'json';
			req.send(
				   'action=wwu2f_sign'
				+ '&data=' + encodeURIComponent(JSON.stringify(data))
				+ '&rememberme=' + remember
				+ '&redirect_to=' + redirect
				+ '&user_id=' + user_id
				+ '&interim_login=' + interim_login
			);
		}
	}

	function doSign()
	{
		form.removeAttribute('hidden');
		lerr.setAttribute('hidden', '');
		u2f.sign(wwU2F.request[0].appId, wwU2F.request[0].challenge, wwU2F.request, signCallback);
	}

	function magic()
	{
		$      = document.getElementById.bind(document);
		form   = $('u2f-form');
		submit = form.querySelector('p.submit');
		pbar   = $('progressbar');
		lerr   = $('login_error');

		form.addEventListener('submit', function(e) {
			e.preventDefault();
		});

		if (typeof u2f === 'undefined' || !u2f.sign) {
			showError(wwU2F.noSupport);
			return;
		}

		submit.querySelector('.button').addEventListener('click', function() {
			pbar.removeAttribute('hidden');
			submit.setAttribute('hidden', '');
			doSign();
		});

		$('interact').removeAttribute('hidden');
		doSign();
	}

	function callback()
	{
		if (typeof u2f === 'undefined' || !u2f.sign) {
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
