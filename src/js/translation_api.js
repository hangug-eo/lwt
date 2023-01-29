/**
 * \file
 * \brief Standard JS interface to get translations
 * 
 * @package Lwt
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   1.6.16-fork
 */

function deleteTranslation (){
	var w = window.parent.frames['ro'];
	if (typeof w == 'undefined') w = window.opener;
	if($('[name="WoTranslation"]',w.document).val().trim().length){
		$('[name="WoTranslation"]',w.document).val('');
		w.makeDirty();
	}
}

function addTranslation (s) {
	var w = window.parent.frames['ro'];
	if (typeof w == 'undefined') w = window.opener;
	if (typeof w == 'undefined') {
		alert ('Translation can not be copied!');
		return;
	}
	var c = w.document.forms[0].WoTranslation;
	if (typeof c != 'object') {
		alert ('Translation can not be copied!');
		return;
	}
	var oldValue = c.value;
	if (oldValue.trim() == '') {
		c.value = s;
		w.makeDirty();
	} else {
		if (oldValue.indexOf(s) == -1) {
			c.value = oldValue + ' / ' + s;
			w.makeDirty();
		} else {
			if (confirm(
				'"' + s + '" seems already to exist as a translation.\nInsert anyway?'
				)) { 
				c.value = oldValue + ' / ' + s;
				w.makeDirty();
			}
		}
	}
}

function getGlosbeTranslation(text, lang, dest) {
	$.ajax({
		url:'http://glosbe.com/gapi/translate?from=' + lang + '&dest=' + dest + 
		'&format=json&phrase=' + text + '&callback=?',
		type:"GET",
		dataType: 'jsonp',
		jsonp: 'getTranslationFromGlosbeApi',
		jsonpCallback: 'getTranslationFromGlosbeApi',
		async:'true'
	});
}

function getTranslationFromGlosbeApi(data) {
	try {
		$.each(data.tuc,function(i,rows){
			if(rows.phrase){
				$('#translations')
				.append(
					'<span class="click" onclick="addTranslation(\'' + 
					rows.phrase.text + '\');">' + 
						'<img src="icn/tick-button.png" title="Copy" alt="Copy" />' + 
						' &nbsp; ' + rows.phrase.text + 
					'</span><br />'
				);
			} else if(rows.meanings){
				$('#translations')
				.append(
					'<span class="click" onclick="addTranslation(' + "'(" + 
					rows.meanings[0].text + ")'" + ');">' + 
						'<img src="icn/tick-button.png" title="Copy" alt="Copy" />' +
						' &nbsp; ' + "(" + rows.meanings[0].text + ")" + 
					'</span><br />'
				);
			}
		});
		if(!data.tuc.length){
			$('#translations')
			.before(
				'<p>No translations found (' + data.from + '-' + data.dest + ').</p>'
			);
			if(data.dest!='en' && data.from!='en'){
				$('#translations').attr('id','no_trans')
				.after(
					'<hr /><p>&nbsp;</p><h3><a href="http://glosbe.com/' + 
					data.from + '/en/' + data.phrase + '">Glosbe Dictionary (' + 
					data.from + '-en):  &nbsp; <span class="red2">' + 
					data.phrase + '</span></a></h3>&nbsp;<p id="translations"></p>'
				);
				getGlosbeTranslation(data.phrase,data.from,'en');
			} else $('#translations').after('<hr />');
		} else 
			$('#translations')
			.after('<p>&nbsp;<br/>' + data.tuc.length + ' translation' + 
			(data.tuc.length==1 ? '' : 's') + 
			' retrieved via <a href="http://glosbe.com/a-api" target="_blank">' + 
			'Glosbe API</a>.</p><hr />'
			);
	}
	catch(err) {
		$('#translations')
		.text('Retrieval error. Possible reason: There is a limit of Glosbe API calls that may be done from one IP address in a fixed period of time, to prevent from abuse.').after('<hr />');
	}
}

/**
 * Base function to get a translation from LibreTranslate.
 * 
 * @param {string}           text Text to translate
 * @param {string}           lang Source language (language of the text, two letters or "auto")
 * @param {string}           dest Destination language (two language)
 * @param {string|undefined} key  Optional API key
 * @param {string}           url  API URL
 * @returns {string} Translation
 */
async function getLibreTranslateTranslationBase(text, lang, dest, key="", url="http://localhost:5000/translate") {
	const res = await fetch(
		url, 
		{
			method: "POST",
			body: JSON.stringify({
				q: text,
				source: lang,
				target: dest,
				format: "text",
				api_key: key
			}),
			headers: { "Content-Type": "application/json" }
		}
	);

	const data = await res.json();
	return data.translatedText;
}

/**
 * Main wrapper for LibreTranslate translation.
 * 
 * @param {string} api_parts API parts separated by spaces. Normally "libretranslate url key".
 * @param {string} text      Text to translate
 * @param {string} lang      Source language (language of the text, two letters or "auto")
 * @param {string} dest      Destination language (two language)
 * @returns {string} Translation
 */
async function getLibreTranslateTranslation(api_parts, text, lang, dest) {
	const parts = api_parts.split(' ');
	if (parts[0] != "libretranslate") {
		throw 'Translation API not supported: ' + parts[0] + "!";
	} 
	return await getLibreTranslateTranslationBase(text, lang, dest, key=parts[2], parts[1])
}