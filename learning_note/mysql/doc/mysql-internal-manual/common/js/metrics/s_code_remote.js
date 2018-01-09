/* SiteCatalyst code version: H.19.BUILD DATE: 25-APR-2014 COPYRIGHT ORACLE CORP 2014 [UNLESS STATED OTHERWISE] */
/* Removed sungloal suite 25-APR-14. Fixed the issue for tracking old code - lists.mysql.com */
/************************** CONFIG SECTION ****************************************/
/* Specify the Report Suite(s) */
var s_account="sunmysqldev";
var sun_dynamicAccountSelection=true;
var sun_dynamicAccountList="sunmysql=mysql.com;sunmysql=mysql.de;sunmysql=mysql.fr;sunmysql=mysql.it;sunmysqldev=.";
/* Specify the Site ID */
var s_siteid="mysql:";
/* PageName Settings */
if (typeof s_pageType=='undefined') {
	var s_pageName=window.location.host+window.location.pathname;
	s_pageName=s_pageName.replace(/www.mysql.com/,"");
	s_pageName=s_pageName.replace(/www.mysql./,"");
	s_pageName=s_pageName.replace(/.mysql.com/,":");
	s_pageName=s_pageName.replace(/mysql.com/,"");
}
/* Omniture JS call  */
var sun_ssl=(window.location.protocol.toLowerCase().indexOf("https")!=-1);
	if(sun_ssl == true) { var fullURL = "https://www.mysql.com/common/js/metrics/metrics_group1.js"; }
		else { var fullURL= "http://www.mysql.com/common/js/metrics/metrics_group1.js"; }
document.write("<sc" + "ript type=\"text/javascript\" src=\""+fullURL+"\"></sc" + "ript>");
/************************** END CONFIG SECTION **************************************/
/* Split dev.mysql.com Path  */
var mysql_host=location.hostname;
if (mysql_host=='dev.mysql.com') {
	var mysql_url=window.location.pathname.toLowerCase();
	var mysql_split=mysql_url.split("/");
}
/* Reset PageName Settings  */
function s_postPlugins(s) {
/* dev.mysql.com/doc ---> prop31  */
if (mysql_host=='dev.mysql.com') {
	if (mysql_split[1]=="doc") {
		s.prop31=s.pageName;
		s.pageName=s.channel+" (site section)";
		}
	}
/* lists.mysql.com ---> prop31  */
	if (mysql_host=='lists.mysql.com') {
		s.prop31=s.pageName;
		s.pageName=s.channel+" (site section)";
	}
}
