/****
 * This is the BandPage connect bootstrapper.  
 */
;(function(){var e="www.bandpage.com",t="/embedscript/connect";window._rm_lightningjs||function(e){function n(n,r){var i="1";return r&&(r+=(/\?/.test(r)?"&":"?")+"lv="+i),e[n]||function(){var i=window,s=document,o=n,u=s.location.protocol,a="load",f=0;(function(){function l(){n.P(a),n.w=1,e[o]("_load")}e[o]=function(){function a(){return a.id=s,e[o].apply(a,arguments)}var t=arguments,r=this,s=++f,u=r&&r!=i?r.id||0:0;return(n.s=n.s||[]).push([s,u,t]),a.then=function(e,t,r){var i=n.fh[s]=n.fh[s]||[],o=n.eh[s]=n.eh[s]||[],u=n.ph[s]=n.ph[s]||[];return e&&i.push(e),t&&o.push(t),r&&u.push(r),a},a};var n=e[o]._={};n.fh={},n.eh={},n.ph={},n.l=r?r.replace(/^\/\//,(u=="https:"?u:"http:")+"//"):r,n.p={0:+(new Date)},n.P=function(e){n.p[e]=new Date-n.p[0]},n.w&&l(),i.addEventListener?i.addEventListener(a,l,!1):i.attachEvent("on"+a,l),n.l&&function(){function e(){return["<head></head><",r,' onload="var d=',p,";d.getElementsByTagName('head')[0].",u,"(d.",a,"('script')).",f,"='",n.l,"'\"></",r,">"].join("")}var r="body",i=s[r];if(!i)return setTimeout(arguments.callee,100);n.P(1);var u="appendChild",a="createElement",f="src",l=s[a]("div"),c=l[u](s[a]("div")),h=s[a]("iframe"),p="document",d="domain",v,m="contentWindow";l.style.display="none",i.insertBefore(l,i.firstChild).id=t+"-"+o,h.frameBorder="0",h.id=t+"-frame-"+o,/MSIE[ ]+6/.test(navigator.userAgent)&&(h[f]="javascript:false"),h.allowTransparency="true",c[u](h);try{h[m][p].open()}catch(g){n[d]=s[d],v="javascript:var d="+p+".open();d.domain='"+s.domain+"';",h[f]=v+"void(0);"}try{var y=h[m][p];y.write(e()),y.close()}catch(b){h[f]=v+'d.write("'+e().replace(/"/g,String.fromCharCode(92)+'"')+'");d.close();'}n.P(2)}()})()}(),e[n].lv=i,e[n]}var t="_rm_lightningjs",r=window[t]=n(t);r.require=n,r.modules=e}({}),function(n){if(n.bandpage)return;var r=_rm_lightningjs.require("$rm","//"+e+t),i=function(){},s=function(t){t.done||(t.done=i),t.fail||(t.fail=i);var n=r("load",t);n.then(t.done,t.fail);var s={done:function(e){return n.then(e,i),s},fail:function(e){return n.then(i,e),s}};return s},o=null;n.bandpage={load:s,ready:function(e){o.then(e,i)}},o=r("bootstrap",n.bandpage,window)}(window)})(this);

jQuery.unserialize = function(serializedString){
    var str = decodeURI(serializedString);
    var pairs = str.split('&');
    var obj = {}, p, idx, val;
    for (var i=0, n=pairs.length; i < n; i++) {
        p = pairs[i].split('=');
        idx = p[0];

        if (idx.indexOf("[]") == (idx.length - 2)) {
            // Eh um vetor
            var ind = idx.substring(0, idx.length-2)
            if (obj[ind] === undefined) {
                obj[ind] = [];
            }
            obj[ind].push(p[1]);
        }
        else {
            obj[idx] = p[1];
        }
    }
    return obj;
};


