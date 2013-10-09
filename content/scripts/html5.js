/*
 * "Creates" some HTML5 elements so that they can be styled in IE8.
 */
var str="article,header,footer";
var n=str.split(",");
for(i = 0; i < n.length; i++) {
    document.createElement(n[i]);
}
