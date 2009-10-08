// DE lang variables
var metaKey;
if (navigator.userAgent.indexOf('Mac OS') != -1) {
// Mac OS browsers use Ctrl to hit accesskeys
	metaKey = 'Ctrl';
}
else {
	metaKey = 'Alt';
}

tinyMCE.addI18n({de:{
FloatBoxPlus:{
desc : 'Video via FBP einfügen'
}}});