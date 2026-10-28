(()=>{var Ir=Array.isArray,Mo=Array.prototype.indexOf,$n=Array.prototype.includes,Do=Array.from,Rn=Object.keys,Vt=Object.defineProperty,Dt=Object.getOwnPropertyDescriptor,Uo=Object.getOwnPropertyDescriptors,Ho=Object.prototype,Vo=Array.prototype,Na=Object.getPrototypeOf,va=Object.isExtensible,dt=()=>{};function Fo(e){for(var t=0;t<e.length;t++)e[t]()}function Ma(){var e,t,n=new Promise((r,a)=>{e=r,t=a});return{promise:n,resolve:e,reject:t}}var de=2,Ft=4,Dn=8,Pr=1<<24,He=16,ze=32,nt=64,hr=128,Or=256,Ne=512,le=1024,ce=2048,je=4096,Me=8192,$e=16384,$t=32768,In=1<<25,pt=65536,Pn=1<<17,jo=1<<18,Rt=1<<19,zo=1<<20,Ct=65536,On=1<<21,Ut=1<<22,vt=1<<23,Ht=Symbol("$state"),Da=Symbol("component"),Bo=Symbol("legacy props"),Ko=Symbol(""),Ua=Symbol("attributes"),dr=Symbol("class"),vr=Symbol("style"),pr=Symbol("text"),on=Symbol("form reset"),fn=new class extends Error{name="StaleReactionError";message="The reaction that called `getAbortSignal()` was re-run or destroyed"},hn=!!globalThis.document?.contentType&&globalThis.document.contentType.includes("xml"),dn=3,vn=8;function Ha(e){return e===this.v}function Va(e,t){return e!=e?t==t:e!==t||e!==null&&typeof e=="object"||typeof e=="function"}function Yo(e){return!Va(e,this.v)}function Go(e){throw new Error("https://svelte.dev/e/lifecycle_outside_component")}function qo(){throw new Error("https://svelte.dev/e/async_derived_orphan")}function Wo(e){throw new Error("https://svelte.dev/e/effect_in_teardown")}function Xo(){throw new Error("https://svelte.dev/e/effect_in_unowned_derived")}function Jo(e){throw new Error("https://svelte.dev/e/effect_orphan")}function Zo(){throw new Error("https://svelte.dev/e/effect_update_depth_exceeded")}function Qo(){throw new Error("https://svelte.dev/e/hydration_failed")}function es(){throw new Error("https://svelte.dev/e/state_descriptors_fixed")}function ts(){throw new Error("https://svelte.dev/e/state_prototype_fixed")}function ns(){throw new Error("https://svelte.dev/e/state_unsafe_mutation")}function rs(){throw new Error("https://svelte.dev/e/svelte_boundary_reset_onerror")}var as=1,is=2,Lr="[",Fa="[!",pa="[?",ja="]",At={},re=Symbol("uninitialized"),za="http://www.w3.org/1999/xhtml",os="http://www.w3.org/2000/svg",ss="http://www.w3.org/1998/Math/MathML",ls="@attach",be=null;function jt(e){be=e}function ct(e,t=!1,n){be={p:be,i:!1,c:null,e:null,s:e,x:null,r:$,l:null}}function ut(e){var t=be,n=t.e;if(n!==null){t.e=null;for(var r of n)bi(r)}return e!==void 0&&(t.x=e),t.i=!0,be=t.p,Nr(e)}function Nr(e={}){return Vt(e,Da,{value:!0}),e}function Ba(){return!0}var wt=[];function Ka(){var e=wt;wt=[],Fo(e)}function Ze(e){if(wt.length===0&&!sn){var t=wt;queueMicrotask(()=>{t===wt&&Ka()})}wt.push(e)}function cs(){for(;wt.length>0;)Ka()}function us(){console.warn("https://svelte.dev/e/derived_inert")}function pn(e){console.warn("https://svelte.dev/e/hydration_mismatch")}function fs(){console.warn("https://svelte.dev/e/select_multiple_invalid_value")}function hs(){console.warn("https://svelte.dev/e/svelte_boundary_reset_noop")}var T=!1;function Qe(e){T=e}var M;function we(e){if(e===null)throw pn(),At;return M=e}function Tt(){return we(at(M))}function ae(e){if(T){if(at(M)!==null)throw pn(),At;M=e}}function Mr(e=1){if(T){for(var t=e,n=M;t--;)n=at(n);M=n}}function Dr(e=!0){for(var t=0,n=M;;){if(n.nodeType===vn){var r=n.data;if(r===ja){if(t===0)return n;t-=1}else(r===Lr||r===Fa||r[0]==="["&&!isNaN(Number(r.slice(1))))&&(t+=1)}var a=at(n);e&&n.remove(),n=a}}function Ya(e){if(!e||e.nodeType!==vn)throw pn(),At;return e.data}function st(e){if(typeof e!="object"||e===null||Ht in e||Da in e)return e;let t=Na(e);if(t!==Ho&&t!==Vo)return e;var n=new Map,r=Ir(e),a=I(0),i=St,l=s=>{if(St===i)return s();var f=O,c=St;De(null),ka(i);var h=s();return De(f),ka(c),h};return r&&n.set("length",I(e.length)),new Proxy(e,{defineProperty(s,f,c){(!("value"in c)||c.configurable===!1||c.enumerable===!1||c.writable===!1)&&es();var h=n.get(f);return h===void 0?l(()=>{var d=I(c.value);return n.set(f,d),d}):_(h,c.value,!0),!0},deleteProperty(s,f){var c=n.get(f);if(c===void 0){if(f in s){let h=l(()=>I(re));n.set(f,h),ln(a)}}else _(c,re),ln(a);return!0},get(s,f,c){if(f===Ht)return e;var h=n.get(f),d=f in s;if(h===void 0&&(!d||Dt(s,f)?.writable)&&(h=l(()=>{var g=st(d?s[f]:re),m=I(g);return m}),n.set(f,h)),h!==void 0){var p=o(h);return p===re?void 0:p}return Reflect.get(s,f,c)},getOwnPropertyDescriptor(s,f){var c=Reflect.getOwnPropertyDescriptor(s,f);if(c&&"value"in c){var h=n.get(f);h&&(c.value=o(h))}else if(c===void 0){var d=n.get(f),p=d?.v;if(d!==void 0&&p!==re)return{enumerable:!0,configurable:!0,value:p,writable:!0}}return c},has(s,f){if(f===Ht)return!0;var c=n.get(f),h=c!==void 0&&c.v!==re||Reflect.has(s,f);if(c!==void 0||$!==null&&(!h||Dt(s,f)?.writable)){c===void 0&&(c=l(()=>{var p=h?st(s[f]):re,g=I(p);return g}),n.set(f,c));var d=o(c);if(d===re)return!1}return h},set(s,f,c,h){var d=n.get(f),p=f in s;if(r&&f==="length")for(var g=c;g<d.v;g+=1){var m=n.get(g+"");m!==void 0?_(m,re):g in s&&(m=l(()=>I(re)),n.set(g+"",m))}if(d===void 0)(!p||Dt(s,f)?.writable)&&(d=l(()=>I(void 0)),_(d,st(c)),n.set(f,d));else{p=d.v!==re;var x=l(()=>st(c));_(d,x)}var P=Reflect.getOwnPropertyDescriptor(s,f);if(P?.set&&P.set.call(h,c),!p){if(r&&typeof f=="string"){var B=n.get("length"),V=Number(f);Number.isInteger(V)&&V>=B.v&&_(B,V+1)}ln(a)}return!0},ownKeys(s){o(a);var f=Reflect.ownKeys(s).filter(d=>{var p=n.get(d);return p===void 0||p.v!==re});for(var[c,h]of n)h.v!==re&&!(c in s)&&f.push(c);return f},setPrototypeOf(){ts()}})}function ga(e){try{if(e!==null&&typeof e=="object"&&Ht in e)return e[Ht]}catch{}return e}function Ga(e,t){return Object.is(ga(e),ga(t))}var Et,gr,qa,Wa,Xa;function mr(){if(Et===void 0){Et=window,gr=document,qa=/Firefox/.test(navigator.userAgent);var e=Element.prototype,t=Node.prototype,n=Text.prototype;Wa=Dt(t,"firstChild").get,Xa=Dt(t,"nextSibling").get,va(e)&&(e[dr]=void 0,e[Ua]=null,e[vr]=void 0,e.__e=void 0),va(n)&&(n[pr]=void 0)}}function Be(e=""){return document.createTextNode(e)}function Ee(e){return Wa.call(e)}function at(e){return Xa.call(e)}function he(e,t){if(!T)return Ee(e);var n=Ee(M);if(n===null)n=M.appendChild(Be());else if(t&&n.nodeType!==dn){var r=Be();return n?.before(r),we(r),r}return t&&Un(n),we(n),n}function Lt(e,t=!1){if(!T){var n=Ee(e);return n instanceof Comment&&n.data===""?at(n):n}if(t){if(M?.nodeType!==dn){var r=Be();return M?.before(r),we(r),r}Un(M)}return M}function Nt(e,t=!1){if(!T)return Ee(e);var n=he(e,t);return ae(e),n}function X(e,t=1,n=!1){let r=T?M:e;for(var a;t--;)a=r,r=at(r);if(!T)return r;if(n){if(r?.nodeType!==dn){var i=Be();return r===null?a?.after(i):r.before(i),we(i),i}Un(r)}return we(r),r}function ds(e){e.textContent=""}function vs(){return!1}function Ur(e,t,n){return t==null||t===za?n?document.createElement(e,{is:n}):document.createElement(e):n?document.createElementNS(t,e,{is:n}):document.createElementNS(t,e)}function Un(e){if(e.nodeValue.length<65536)return;let t=e.nextSibling;for(;t!==null&&t.nodeType===dn;)t.remove(),e.nodeValue+=t.nodeValue,t=e.nextSibling}function ps(e){var t=$;if(t===null)return O.f|=vt,e;if((t.f&$t)===0&&(t.f&Ft)===0)throw e;Je(e,t)}function Je(e,t){if(!(t!==null&&(t.f&$e)!==0)){for(;t!==null;){if((t.f&hr)!==0&&(t.f&($e|In))===0){if((t.f&$t)===0)throw e;try{t.b.error(e);return}catch(n){e=n}}t=t.parent}throw e}}var gs=-7169;function te(e,t){e.f=e.f&gs|t}function Hr(e){(e.f&Ne)!==0||e.deps===null?te(e,le):te(e,je)}function Ja(e){if(e!==null)for(let t of e)(t.f&de)===0||(t.f&Ct)===0||(t.f^=Ct,Ja(t.deps))}function Za(e,t,n){(e.f&ce)!==0?t.add(e):(e.f&je)!==0&&n.add(e),Ja(e.deps),te(e,le)}function Qa(e,t,n){if(e==null)return t(void 0),dt;let r=bn(()=>e.subscribe(t,n));return r.unsubscribe?()=>r.unsubscribe():r}var Pt=[];function ms(e,t=dt){let n=null,r=new Set;function a(s){if(Va(e,s)&&(e=s,n)){let f=!Pt.length;for(let c of r)c[1](),Pt.push(c,e);if(f){for(let c=0;c<Pt.length;c+=2)Pt[c][0](Pt[c+1]);Pt.length=0}}}function i(s){a(s(e))}function l(s,f=dt){let c=[s,f];return r.add(c),r.size===1&&(n=t(a,i)||dt),s(e),()=>{r.delete(c),r.size===0&&n&&(n(),n=null)}}return{set:a,update:i,subscribe:l}}function nn(e){let t;return Qa(e,n=>t=n)(),t}var br=Symbol("unmounted");function ma(e,t,n){let r=n[t]??={store:null,source:ci(void 0),unsubscribe:dt};if(r.store!==e&&!(br in n))if(r.unsubscribe(),r.store=e??null,e==null)r.source.v=void 0,r.unsubscribe=dt;else{var a=!0;r.unsubscribe=Qa(e,i=>{a?r.source.v=i:_(r.source,i)}),a=!1}return e&&br in n?nn(e):o(r.source)}function bs(){let e={};function t(){Hn(()=>{for(var n in e)e[n].unsubscribe();Vt(e,br,{enumerable:!1,value:!0})})}return[e,t]}function ys(e,t){if(t){let n=document.body;e.autofocus=!0,Ze(()=>{document.activeElement===n&&e.focus()})}}var ba=!1;function ei(){ba||(ba=!0,document.addEventListener("reset",e=>{Promise.resolve().then(()=>{if(!e.defaultPrevented)for(let t of e.target.elements)t[on]?.()})},{capture:!0}))}function Kt(e){var t=O,n=$;De(null),rt(null);try{return e()}finally{De(t),rt(n)}}function _s(e,t,n,r=n){e.addEventListener(t,()=>Kt(n));let a=e[on];a?e[on]=()=>{a(),r(!0)}:e[on]=()=>r(!0),ei()}function ti(e,t,n,r){let a=Vr;var i=e.filter(g=>!g.settled),l=t.map(a);if(n.length===0&&i.length===0){r(l);return}var s=$,f=ws(),c=i.length===1?i[0].promise:i.length>1?Promise.all(i.map(g=>g.promise)):null;function h(g){if((s.f&$e)===0){f();try{r([...l,...g])}catch(m){Je(m,s)}Ln()}}var d=ni();if(n.length===0){c.then(()=>h([])).finally(d);return}function p(){Promise.all(n.map(g=>ks(g))).then(h).catch(g=>Je(g,s)).finally(d)}c?c.then(()=>{f(),p(),Ln()}):p()}function ws(){var e=$,t=O,n=be,r=R;return function(i=!0){rt(e),De(t),jt(n),i&&(e.f&$e)===0&&(r?.activate(),r?.apply())}}function Ln(e=!0){rt(null),De(null),jt(null),e&&R?.deactivate()}function ni(){var e=$,t=e.b,n=R,r=!!t?.is_rendered();return t?.update_pending_count(1,n),n.increment(r,e),()=>{t?.update_pending_count(-1,n),n.decrement(r,e)}}function Vr(e){var t=de|ce;return $!==null&&($.f|=Rt),{ctx:be,deps:null,effects:null,equals:Ha,f:t,fn:e,reactions:null,rv:0,v:re,wv:0,parent:$,ac:null}}var rn=Symbol("obsolete");function ks(e,t,n){let r=$;r===null&&qo();var a=void 0,i=gn(re),l=!O,s=new Set;return Ls(()=>{var f=$,c=Ma();a=c.promise;try{Promise.resolve(e()).then(c.resolve,g=>{g!==fn&&c.reject(g)}).finally(Ln)}catch(g){c.reject(g),Ln()}var h=R;if(l){if((f.f&$t)!==0)var d=ni();if(r.b?.is_rendered())h.async_deriveds.get(f)?.reject(rn);else for(let g of s.values())g.reject(rn);s.add(c),h.async_deriveds.set(f,c)}let p=(g,m=void 0)=>{d?.(),s.delete(c),m!==rn&&(h.activate(),m?(i.f|=vt,Mn(i,m)):((i.f&vt)!==0&&(i.f^=vt),Mn(i,g)),h.deactivate())};c.promise.then(p,g=>p(null,g||"unknown"))}),Hn(()=>{for(let f of s)f.reject(rn)}),new Promise(f=>{function c(h){function d(){h===a?f(i):c(a)}h.then(d,d)}c(a)})}function _e(e){let t=Vr(e);return fi(t),t}function xs(e){var t=e.effects;if(t!==null){e.effects=null;for(var n=0;n<t.length;n+=1)pe(t[n])}}function Fr(e){var t,n=$,r=e.parent;if(!lt&&r!==null&&e.v!==re&&(r.f&($e|Me))!==0)return us(),e.v;rt(r);try{e.f&=~Ct,xs(e),t=pi(e)}finally{rt(n)}return t}function ri(e){var t=Fr(e);if(!e.equals(t)&&(e.wv=di(),(!R?.is_fork||e.deps===null)&&(R!==null?(R.capture(e,t,!0),yr?.capture(e,t,!0)):e.v=t,e.deps===null))){te(e,le);return}lt||(Ve!==null?(Br()||R?.is_fork)&&Ve.set(e,t):Hr(e))}function Es(e){if(e.effects!==null)for(let t of e.effects)(t.teardown||t.ac)&&(t.teardown?.(),t.ac!==null&&Kt(()=>{t.ac.abort(fn),t.ac=null}),t.fn!==null&&(t.teardown=dt),un(t,0),Yr(t))}function ai(e){if(e.effects!==null)for(let t of e.effects)t.teardown&&t.fn!==null&&zt(t)}var rr=null,Ot=null,R=null,yr=null,Ve=null,_r=null,sn=!1,ar=!1,Mt=null,Cn=null,ya=0;var Ss=1,gt=class e{id=Ss++;#e=!1;linked=!0;#t=null;#n=null;async_deriveds=new Map;current=new Map;previous=new Map;#l=new Set;#a=new Set;#o=0;#r=new Map;#s=null;#i=[];#p=[];#c=new Set;#u=new Set;#h=new Map;#g=new Set;is_fork=!1;#f=!1;constructor(){Ot===null?rr=Ot=this:(Ot.#n=this,this.#t=Ot),Ot=this}#y(){if(this.is_fork)return!0;for(let r of this.#r.keys()){for(var t=r,n=!1;t.parent!==null;){if(this.#h.has(t)){n=!0;break}t=t.parent}if(!n)return!0}return!1}skip_effect(t){this.#h.has(t)||this.#h.set(t,{d:[],m:[]}),this.#g.delete(t)}unskip_effect(t,n=r=>this.schedule(r)){var r=this.#h.get(t);if(r){this.#h.delete(t);for(var a of r.d)te(a,ce),n(a);for(a of r.m)te(a,je),n(a)}this.#g.add(t)}#m(){this.#e=!0,ya++>1e3&&(this.#v(),Cs());for(let f of this.#c)this.#u.delete(f),te(f,ce),this.schedule(f);for(let f of this.#u)te(f,je),this.schedule(f);let t=this.#i;this.#i=[],this.apply();var n=Mt=[],r=[],a=Cn=[];for(let f of t)try{this.#_(f,n,r)}catch(c){throw si(f),this.#y()||this.discard(),c}if(R=null,a.length>0){var i=e.ensure();for(let f of a)i.schedule(f)}if(Mt=null,Cn=null,this.#y()){this.#d(r),this.#d(n);for(let[f,c]of this.#h)oi(f,c);a.length>0&&R.#m();return}let l=this.#w();if(l){this.#d(r),this.#d(n),l.#k(this);return}this.#c.clear(),this.#u.clear();for(let f of this.#l)f(this);this.#l.clear(),yr=this,_a(r),_a(n),yr=null,this.#s?.resolve();var s=R;if(this.#o===0&&(this.#i.length===0||s!==null)&&this.#v(),this.#i.length>0)if(s!==null){let f=s;f.#i.push(...this.#i.filter(c=>!f.#i.includes(c)))}else s=this;s!==null&&(et.clear(),s.#m())}#_(t,n,r){t.f^=le;for(var a=t.first;a!==null;){var i=a.f,l=(i&(ze|nt))!==0,s=l&&(i&le)!==0,f=s||(i&Me)!==0||this.#h.has(a);if(!f&&a.fn!==null){l?a.f^=le:(i&Ft)!==0?n.push(a):mn(a)&&((i&He)!==0&&this.#u.add(a),zt(a));var c=a.first;if(c!==null){a=c;continue}}for(;a!==null;){var h=a.next;if(h!==null){a=h;break}a=a.parent}}}#w(){for(var t=this.#t;t!==null;){if(!t.is_fork){for(let[n,[,r]]of this.current)if(t.current.has(n)&&!r)return t}t=t.#t}return null}#k(t){for(let[r,a]of t.current)!this.previous.has(r)&&t.previous.has(r)&&this.previous.set(r,t.previous.get(r)),this.current.set(r,a);for(let[r,a]of t.async_deriveds){let i=this.async_deriveds.get(r);i&&a.promise.then(i.resolve).catch(i.reject)}t.async_deriveds.clear(),this.transfer_effects(t.#c,t.#u);let n=r=>{var a=r.reactions;if(a!==null&&!((r.f&de)!==0&&(r.f&(ce|je))===0))for(let s of a){var i=s.f;if((i&de)!==0)n(s);else{var l=s;i&(Ut|He)&&!this.async_deriveds.has(l)&&(this.#u.delete(l),te(l,ce),this.schedule(l))}}};for(let r of this.current.keys())n(r);this.oncommit(()=>t.discard()),t.#v(),R=this,this.#m()}#d(t){for(var n=0;n<t.length;n+=1)Za(t[n],this.#c,this.#u)}capture(t,n,r=!1){t.v!==re&&!this.previous.has(t)&&this.previous.set(t,t.v),(t.f&vt)===0&&(this.current.set(t,[n,r]),Ve?.set(t,n)),this.is_fork||(t.v=n)}activate(){R=this}deactivate(){R=null,Ve=null}flush(){try{ar=!0,R=this,this.#m()}finally{ya=0,_r=null,Mt=null,Cn=null,ar=!1,R=null,Ve=null,et.clear()}}discard(){for(let t of this.#a)t(this);this.#a.clear();for(let t of this.async_deriveds.values())t.reject(rn);this.#v(),this.#s?.resolve()}register_created_effect(t){this.#p.push(t)}#b(){for(let d=rr;d!==null;d=d.#n){var t=d.id<this.id,n=[];for(let[p,[g,m]]of this.current){if(d.current.has(p)){var r=d.current.get(p)[0];if(t&&g!==r)d.current.set(p,[g,m]);else continue}n.push(p)}if(t)for(let[p,g]of this.async_deriveds){let m=d.async_deriveds.get(p);m&&g.promise.then(m.resolve).catch(m.reject)}var a=[...d.current.keys()].filter(p=>!d.current.get(p)[1]);if(!(!d.#e||a.length===0)){var i=a.filter(p=>!this.current.has(p));if(i.length===0)t&&d.discard();else if(n.length>0){if(t)for(let p of this.#g)d.unskip_effect(p,g=>{(g.f&(He|Ut))!==0?d.schedule(g):d.#d([g])});d.activate();var l=new Set,s=new Map;for(var f of n)ii(f,i,l,s);s=new Map;var c=[...d.current].filter(([p,g])=>{let m=this.current.get(p);return m?m[0]!==g[0]||m[1]!==g[1]:!0}).map(([p])=>p);if(c.length>0)for(let p of this.#p)(p.f&($e|Me|Pn))===0&&jr(p,c,s)&&((p.f&(Ut|He))!==0?(te(p,ce),d.schedule(p)):d.#c.add(p));if(d.#i.length>0&&!d.#f){d.apply();for(var h of d.#i)d.#_(h,[],[]);d.#i=[]}d.deactivate()}}}}increment(t,n){if(this.#o+=1,t){let r=this.#r.get(n)??0;this.#r.set(n,r+1)}}decrement(t,n){if(this.#o-=1,t){let r=this.#r.get(n)??0;r===1?this.#r.delete(n):this.#r.set(n,r-1)}this.#f||(this.#f=!0,Ze(()=>{this.#f=!1,this.linked&&this.flush()}))}transfer_effects(t,n){for(let r of t)this.#c.add(r);for(let r of n)this.#u.add(r);t.clear(),n.clear()}oncommit(t){this.#l.add(t)}ondiscard(t){this.#a.add(t)}settled(){return(this.#s??=Ma()).promise}static ensure(){if(R===null){let t=R=new e;!ar&&!sn&&Ze(()=>{t.#e||t.flush()})}return R}apply(){{Ve=null;return}}schedule(t){if(_r=t,t.b?.is_pending&&(t.f&(Ft|Dn|Pr))!==0&&(t.f&$t)===0){t.b.defer_effect(t);return}for(var n=t;n.parent!==null;){n=n.parent;var r=n.f;if(Mt!==null&&n===$&&(O===null||(O.f&de)===0))return;if((r&(nt|ze))!==0){if((r&le)===0)return;n.f^=le}}this.#i.push(n)}#v(){if(this.linked){var t=this.#t,n=this.#n;t===null?rr=n:t.#n=n,n===null?Ot=t:n.#t=t,this.linked=!1}}};function Y(e){var t=sn;sn=!0;try{for(var n;;){if(cs(),R===null)return n;R.flush()}}finally{sn=t}}function Cs(){try{Zo()}catch(e){Je(e,_r)}}var ot=null;function _a(e){var t=e.length;if(t!==0){for(var n=0;n<t;){var r=e[n++];if((r.f&($e|Me))===0&&mn(r)&&(ot=new Set,zt(r),r.deps===null&&r.first===null&&r.nodes===null&&r.teardown===null&&r.ac===null&&ki(r),ot?.size>0)){et.clear();for(let a of ot){if((a.f&($e|Me))!==0)continue;let i=[a],l=a.parent;for(;l!==null;)ot.has(l)&&(ot.delete(l),i.push(l)),l=l.parent;for(let s=i.length-1;s>=0;s--){let f=i[s];(f.f&($e|Me))===0&&zt(f)}}ot.clear()}}ot=null}}function ii(e,t,n,r){if(!n.has(e)&&(n.add(e),e.reactions!==null))for(let a of e.reactions){let i=a.f;(i&de)!==0?ii(a,t,n,r):(i&(Ut|He))!==0&&(i&ce)===0&&jr(a,t,r)&&(te(a,ce),zr(a))}}function jr(e,t,n){let r=n.get(e);if(r!==void 0)return r;if(e.deps!==null)for(let a of e.deps){if($n.call(t,a))return!0;if((a.f&de)!==0&&jr(a,t,n))return n.set(a,!0),!0}return n.set(e,!1),!1}function zr(e){R.schedule(e)}function oi(e,t){if(!((e.f&ze)!==0&&(e.f&le)!==0)){(e.f&ce)!==0?t.d.push(e):(e.f&je)!==0&&t.m.push(e),te(e,le);for(var n=e.first;n!==null;)oi(n,t),n=n.next}}function si(e){te(e,le);for(var t=e.first;t!==null;)si(t),t=t.next}var Nn=new Set,et=new Map,li=!1;function gn(e,t){var n={f:0,v:e,reactions:null,equals:Ha,rv:0,wv:0};return n}function I(e,t){let n=gn(e);return fi(n),n}function ci(e,t=!1,n=!0){let r=gn(e);return t||(r.equals=Yo),r}function _(e,t,n=!1){O!==null&&(!Fe||(O.f&Pn)!==0)&&Ba()&&(O.f&(de|He|Ut|Pn))!==0&&(tt===null||!tt.has(e))&&ns();let r=n?st(t):t;return Mn(e,r,Cn)}function Mn(e,t,n=null){if(!e.equals(t)){lt?et.set(e,t):et.has(e)||et.set(e,e.v);var r=gt.ensure();if(r.capture(e,t),(e.f&de)!==0){let a=e;(e.f&ce)!==0&&Fr(a),Ve===null&&Hr(a)}e.wv=di(),ui(e,ce,n),$!==null&&($.f&le)!==0&&($.f&(ze|nt))===0&&(Le===null?Ts([e]):Le.push(e)),!r.is_fork&&Nn.size>0&&!li&&As()}return t}function As(){li=!1;for(let e of Nn){(e.f&le)!==0&&te(e,je);let t;try{t=mn(e)}catch{t=!0}t&&zt(e)}Nn.clear()}function ln(e){_(e,e.v+1)}function ui(e,t,n){var r=e.reactions;if(r!==null)for(var a=r.length,i=0;i<a;i++){var l=r[i],s=l.f,f=(s&ce)===0;if(f&&te(l,t),(s&Pn)!==0)Nn.add(l);else if((s&de)!==0){var c=l;Ve?.delete(c),(s&Ct)===0&&(s&Ne&&($===null||($.f&On)===0)&&(l.f|=Ct),ui(c,je,n))}else if(f){var h=l;(s&He)!==0&&ot!==null&&ot.add(h),n!==null?n.push(h):zr(h)}}}var An=!1,lt=!1;function wa(e){lt=e}var O=null,Fe=!1;function De(e){O=e}var $=null;function rt(e){$=e}var tt=null;function fi(e){O!==null&&(tt??=new Set).add(e)}var Se=null,Te=0,Le=null;function Ts(e){Le=e}var hi=1,kt=0,St=kt;function ka(e){St=e}function di(){return++hi}function mn(e){var t=e.f;if((t&ce)!==0)return!0;if(t&de&&(e.f&=~Ct),(t&je)!==0){for(var n=e.deps,r=n.length,a=0;a<r;a++){var i=n[a];if(mn(i)&&ri(i),i.wv>e.wv)return!0}(t&Ne)!==0&&Ve===null&&te(e,le)}return!1}function vi(e,t,n=!0){var r=e.reactions;if(r!==null&&!(tt!==null&&tt.has(e)))for(var a=0;a<r.length;a++){var i=r[a];(i.f&de)!==0?vi(i,t,!1):t===i&&(n?te(i,ce):(i.f&le)!==0&&te(i,je),zr(i))}}function pi(e){var t=Se,n=Te,r=Le,a=O,i=tt,l=be,s=Fe,f=St,c=e.f;Se=null,Te=0,Le=null,O=(c&(ze|nt))===0?e:null,tt=null,jt(e.ctx),Fe=!1,St=++kt,e.ac!==null&&(Kt(()=>{e.ac.abort(fn)}),e.ac=null);try{e.f|=On;var h=e.fn,d=h();e.f|=$t;var p=xa(e);if(Ba()&&Le!==null&&!Fe&&p!==null&&(e.f&(de|je|ce))===0)for(var g=0;g<Le.length;g++)vi(Le[g],e);if(a!==null&&a!==e){if(kt++,a.deps!==null)for(let m=0;m<n;m+=1)a.deps[m].rv=kt;if(t!==null)for(let m of t)m.rv=kt;Le!==null&&(r===null?r=Le:r.push(...Le))}return(e.f&vt)!==0&&(e.f^=vt),d}catch(m){return xa(e),ps(m)}finally{e.f^=On,Se=t,Te=n,Le=r,O=a,tt=i,jt(l),Fe=s,St=f}}function xa(e){var t=e.deps,n=R?.is_fork;if(Se!==null){var r;if(n||un(e,Te),t!==null&&Te>0)for(t.length=Te+Se.length,r=0;r<Se.length;r++)t[Te+r]=Se[r];else e.deps=t=Se;if(Br()&&(e.f&Ne)!==0)for(r=Te;r<t.length;r++)(t[r].reactions??=[]).push(e)}else!n&&t!==null&&Te<t.length&&(un(e,Te),t.length=Te);return t}function $s(e,t){let n=t.reactions;if(n!==null){var r=Mo.call(n,e);if(r!==-1){var a=n.length-1;a===0?n=t.reactions=null:(n[r]=n[a],n.pop())}}if(n===null&&(t.f&de)!==0&&(Se===null||!$n.call(Se,t))){var i=t;(i.f&Ne)!==0&&(i.f^=Ne,i.f&=~Ct),i.v!==re&&Hr(i),i.ac!==null&&Kt(()=>{i.ac.abort(fn),i.ac=null,te(i,ce)}),Es(i),un(i,0)}}function un(e,t){var n=e.deps;if(n!==null)for(var r=t;r<n.length;r++)$s(e,n[r])}function zt(e){var t=e.f;if((t&$e)===0){te(e,le);var n=$,r=An;$=e,An=(t&(ze|nt))===0;try{(t&(He|Pr))!==0?Ns(e):Yr(e),_i(e);var a=pi(e);e.teardown=typeof a=="function"?a:null,e.wv=hi;var i}finally{An=r,$=n}}}async function xt(){await Promise.resolve(),Y()}function o(e){var t=e.f,n=(t&de)!==0;if(O!==null&&!Fe){var r=$!==null&&($.f&$e)!==0;if(!r&&(tt===null||!tt.has(e))){var a=O.deps;if((O.f&On)!==0)e.rv<kt&&(e.rv=kt,Se===null&&a!==null&&a[Te]===e?Te++:Se===null?Se=[e]:Se.push(e));else{O.deps??=[],$n.call(O.deps,e)||O.deps.push(e);var i=e.reactions;i===null?e.reactions=[O]:$n.call(i,O)||i.push(O)}}}if(lt&&et.has(e))return et.get(e);if(n){var l=e;if(lt){var s=l.v;return((l.f&le)===0&&l.reactions!==null||mi(l))&&(s=Fr(l)),et.set(l,s),s}var f=(l.f&Ne)===0&&!Fe&&O!==null&&(An||(O.f&Ne)!==0),c=(l.f&$t)===0;mn(l)&&(f&&(l.f|=Ne),ri(l)),f&&!c&&(ai(l),gi(l))}if(Ve?.has(e))return Ve.get(e);if((e.f&vt)!==0)throw e.v;return e.v}function gi(e){if(e.f|=Ne,e.deps!==null)for(let t of e.deps)(t.reactions??=[]).push(e),(t.f&de)!==0&&(t.f&Ne)===0&&(ai(t),gi(t))}function mi(e){if(e.v===re)return!0;if(e.deps===null)return!1;for(let t of e.deps)if(et.has(t)||(t.f&de)!==0&&mi(t))return!0;return!1}function bn(e){var t=Fe;try{return Fe=!0,e()}finally{Fe=t}}function Rs(e){$===null&&(O===null&&Jo(),Xo()),lt&&Wo()}function Is(e,t){var n=t.last;n===null?t.last=t.first=e:(n.next=e,e.prev=n,t.last=e)}function Ke(e,t){var n=$;n!==null&&(n.f&Me)!==0&&(e|=Me);var r={ctx:be,deps:null,nodes:null,f:e|ce|Ne,first:null,fn:t,last:null,next:null,parent:n,b:n&&n.b,prev:null,teardown:null,wv:0,ac:null};R?.register_created_effect(r);var a=r;if((e&Ft)!==0)Mt!==null?Mt.push(r):gt.ensure().schedule(r);else if(t!==null){try{zt(r)}catch(l){throw pe(r),l}a.deps===null&&a.teardown===null&&a.nodes===null&&a.first===a.last&&(a.f&Rt)===0&&(a=a.first,(e&He)!==0&&(e&pt)!==0&&a!==null&&(a.f|=pt))}if(a!==null&&(a.parent=n,n!==null&&Is(a,n),O!==null&&(O.f&de)!==0&&(e&nt)===0)){var i=O;(i.effects??=[]).push(a)}return r}function Br(){return O!==null&&!Fe}function Hn(e){let t=Ke(Dn,null);return te(t,le),t.teardown=e,t}function xe(e){Rs();var t=$.f,n=!O&&(t&ze)!==0&&be!==null&&!be.i;if(n){var r=be;(r.e??=[]).push(e)}else return bi(e)}function bi(e){return Ke(Ft|zo,e)}function Ps(e){gt.ensure();let t=Ke(nt|Rt,e);return()=>{pe(t)}}function Os(e){gt.ensure();let t=Ke(nt|Rt,e);return(n={})=>new Promise(r=>{n.outro?cn(t,()=>{pe(t),r(void 0)}):(pe(t),r(void 0))})}function Kr(e){return Ke(Ft,e)}function Ls(e){return Ke(Ut|Rt,e)}function Vn(e,t=0){return Ke(Dn|t,e)}function me(e,t=[],n=[],r=[]){ti(r,t,n,a=>{Ke(Dn,()=>{e(...a.map(o))})})}function yn(e,t=0){var n=Ke(He|t,e);return n}function yi(e,t=0){var n=Ke(Pr|t,e);return n}function Ue(e){return Ke(ze|Rt,e)}function _i(e){var t=e.teardown;if(t!==null){let n=lt,r=O;wa(!0),De(null);try{t.call(null)}catch(a){Je(a,e.parent)}finally{wa(n),De(r)}}}function Yr(e,t=!1){var n=e.first;for(e.first=e.last=null;n!==null;){let a=n.ac;a!==null&&Kt(()=>{a.abort(fn)});var r=n.next;(n.f&nt)!==0?n.parent=null:pe(n,t),n=r}}function Ns(e){for(var t=e.first;t!==null;){var n=t.next;(t.f&ze)===0&&pe(t),t=n}}function pe(e,t=!0){var n=!1;(t||(e.f&jo)!==0)&&e.nodes!==null&&e.nodes.end!==null&&(wi(e.nodes.start,e.nodes.end),n=!0),e.f|=In,Yr(e,t&&!n),un(e,0);var r=e.nodes&&e.nodes.t;if(r!==null)for(let i of r)i.stop();_i(e),e.f^=In,e.f|=$e;var a=e.parent;a!==null&&a.first!==null&&ki(e),e.next=e.prev=e.teardown=e.ctx=e.deps=e.fn=e.nodes=e.ac=e.b=null}function wi(e,t){for(;e!==null;){var n=e===t?null:at(e);e.remove(),e=n}}function ki(e){var t=e.parent,n=e.prev,r=e.next;n!==null&&(n.next=r),r!==null&&(r.prev=n),t!==null&&(t.first===e&&(t.first=r),t.last===e&&(t.last=n))}function cn(e,t,n=!0){var r=[];e.f|=Or,xi(e,r,!0);var a=()=>{n&&pe(e),t&&t()},i=r.length;if(i>0){var l=()=>--i||a();for(var s of r)s.out(l)}else a()}function xi(e,t,n){if((e.f&Me)===0){e.f^=Me;var r=e.nodes&&e.nodes.t;if(r!==null)for(let s of r)(s.is_global||n)&&t.push(s);for(var a=e.first;a!==null;){var i=a.next;if((a.f&nt)===0){var l=(a.f&pt)!==0||(a.f&ze)!==0&&(e.f&He)!==0;xi(a,t,l?n:!1)}a=i}}}function Ea(e){e.f&=~Or,Ei(e,!0)}function Ei(e,t){if((e.f&Or)===0&&(e.f&Me)!==0){e.f^=Me,(e.f&le)===0&&(te(e,ce),gt.ensure().schedule(e));for(var n=e.first;n!==null;){var r=n.next,a=(n.f&pt)!==0||(n.f&ze)!==0;Ei(n,a?t:!1),n=r}var i=e.nodes&&e.nodes.t;if(i!==null)for(let l of i)(l.is_global||t)&&l.in()}}function Si(e,t){if(e.nodes)for(var n=e.nodes.start,r=e.nodes.end;n!==null;){var a=n===r?null:at(n);t.append(n),n=a}}function Ms(e){let t=0,n=gn(0),r;return()=>{Br()&&(o(n),Vn(()=>(t===0&&(r=bn(()=>e(()=>ln(n)))),t+=1,()=>{Ze(()=>{t-=1,t===0&&(r?.(),r=void 0,ln(n))})})))}}function Sa(e){let t={get:n=>nn(t.store)[n],set:(n,r)=>{typeof n=="string"?Object.assign(nn(t.store),{[n]:r}):Object.assign(nn(t.store),n),t.store.set(nn(t.store))},store:ms(e)};return t}globalThis.$altcha=globalThis.$altcha||{algorithms:new Map,defaults:Sa({}),i18n:Sa({}),instances:new Set,plugins:new Set};var Ds={ariaLinkLabel:"Altcha (official website)",cancel:"Cancel",enterCode:"Enter code",enterCodeAria:"Enter code you hear. Press Space to play audio.",enterCodeFromImage:"To proceed, please enter the code from the image below.",error:"Verification failed. Try again later.",expired:"Verification expired. Try again.",footer:'Protected by <a href="https://altcha.org/" target="_blank" rel="noopener" aria-label="Altcha (official website, opens in new tab)">ALTCHA</a>',getAudioChallenge:"Get an audio challenge",label:"I'm not a robot",loading:"Loading...",reload:"Reload",verify:"Verify",verificationRequired:"Verification required!",verified:"Verified",verifying:"Verifying...",waitAlert:"Verifying... please wait."};globalThis.$altcha.i18n.set("en",Ds);var Us="5";typeof window<"u"&&((window.__svelte??={}).v??=new Set).add(Us);var an=Symbol("events"),Ci=new Set,wr=new Set;function Ai(e,t,n,r={}){function a(i){if(r.capture||kr.call(t,i),!i.cancelBubble)return Kt(()=>n?.call(this,i))}return e.startsWith("pointer")||e.startsWith("touch")||e==="wheel"?Ze(()=>{t.addEventListener(e,a,r)}):t.addEventListener(e,a,r),a}function se(e,t,n,r,a){var i={capture:r,passive:a},l=Ai(e,t,n,i);(t===document.body||t===window||t===document||t instanceof HTMLMediaElement)&&Hn(()=>{t.removeEventListener(e,l,i)})}function Fn(e,t,n){(t[an]??={})[e]=n}function jn(e){for(var t=0;t<e.length;t++)Ci.add(e[t]);for(var n of wr)n(e)}var ir=null,or=!1;function kr(e){var t=this,n=t.ownerDocument,r=e.type,a=e.composedPath?.()||[],i=a[0]||e.target;ir=e,or||(or=!0,setTimeout(()=>{or=!1,ir=null}));var l=0,s=ir===e&&e[an];if(s){var f=a.indexOf(s);if(f!==-1&&(t===document||t===window)){e[an]=t;return}var c=a.indexOf(t);if(c===-1)return;f<=c&&(l=f)}if(i=a[l]||e.target,i!==t){Vt(e,"currentTarget",{configurable:!0,get(){return i||n}});var h=O,d=$;De(null),rt(null);try{for(var p,g=[];i!==null&&i!==t;){try{var m=i[an]?.[r];m!=null&&(!i.disabled||e.target===i)&&m.call(i,e)}catch(x){p?g.push(x):p=x}if(e.cancelBubble)break;l++,i=l<a.length?a[l]:null}if(p){for(let x of g)queueMicrotask(()=>{throw x});throw p}}finally{e[an]=t,delete e.currentTarget,De(h),rt(d)}}}var Hs=globalThis?.window?.trustedTypes&&globalThis.window.trustedTypes.createPolicy("svelte-trusted-html",{createHTML:e=>e});function Vs(e){return Hs?.createHTML(e)??e}function Ti(e){var t=Ur("template");return t.innerHTML=Vs(e.replaceAll("<!>","<!---->")),t.content}function Re(e,t){var n=$;n.nodes===null&&(n.nodes={start:e,end:t,a:null,t:null})}function ee(e,t){var n=(t&as)!==0,r=(t&is)!==0,a,i=!e.startsWith("<!>");return()=>{if(T)return Re(M,null),M;a===void 0&&(a=Ti(i?e:"<!>"+e),n||(a=Ee(a)));var l=r||qa?document.importNode(a,!0):a.cloneNode(!0);if(n){var s=Ee(l),f=l.lastChild;Re(s,f)}else Re(l,l);return l}}function Fs(e,t,n="svg"){var r=!e.startsWith("<!>"),a=`<${n}>${r?e:"<!>"+e}</${n}>`,i;return()=>{if(T)return Re(M,null),M;if(!i){var l=Ti(a),s=Ee(l);i=Ee(s)}var f=i.cloneNode(!0);return Re(f,f),f}}function Gr(e,t){return Fs(e,t,"svg")}function En(e=""){if(!T){var t=Be(e+"");return Re(t,t),t}var n=M;return n.nodeType!==dn?(n.before(n=Be()),we(n)):Un(n),Re(n,n),n}function Ca(){if(T)return Re(M,null),M;var e=document.createDocumentFragment(),t=document.createComment(""),n=Be();return e.append(t,n),Re(t,n),e}function N(e,t){if(T){var n=$;((n.f&$t)===0||n.nodes.end===null)&&(n.nodes.end=M),Tt();return}e!==null&&e.before(t)}function js(e){return e.endsWith("capture")&&e!=="gotpointercapture"&&e!=="lostpointercapture"}var zs=["beforeinput","click","change","dblclick","contextmenu","focusin","focusout","input","keydown","keyup","mousedown","mousemove","mouseout","mouseover","mouseup","pointerdown","pointermove","pointerout","pointerover","pointerup","touchend","touchmove","touchstart"];function Bs(e){return zs.includes(e)}var Ks={formnovalidate:"formNoValidate",ismap:"isMap",nomodule:"noModule",playsinline:"playsInline",readonly:"readOnly",defaultvalue:"defaultValue",defaultchecked:"defaultChecked",srcobject:"srcObject",novalidate:"noValidate",allowfullscreen:"allowFullscreen",disablepictureinpicture:"disablePictureInPicture",disableremoteplayback:"disableRemotePlayback"};function Ys(e){return e=e.toLowerCase(),Ks[e]??e}var Gs=["touchstart","touchmove"];function qs(e){return Gs.includes(e)}var Ws=pt|Rt;function Xs(e,t,n,r){new xr(e,t,n,r)}var xr=class{parent;is_pending=!1;transform_error;#e;#t=T?M:null;#n;#l;#a;#o=null;#r=null;#s=null;#i=null;#p=0;#c=0;#u=!1;#h=new Set;#g=new Set;#f=null;#y=Ms(()=>(this.#f=gn(this.#p),()=>{this.#f=null}));constructor(t,n,r,a){this.#e=t,this.#n=n,this.#l=i=>{var l=$;l.b=this,l.f|=hr,r(i)},this.parent=$.b,this.transform_error=a??this.parent?.transform_error??(i=>i),this.#a=yn(()=>{if(T){let i=this.#t;Tt();let l=i.data===Fa;if(i.data.startsWith(pa)){let f=JSON.parse(i.data.slice(pa.length));this.#_(f)}else l?this.#k():this.#m()}else this.#d()},Ws),T&&(this.#e=M)}#m(){try{this.#o=Ue(()=>this.#l(this.#e))}catch(t){this.error(t)}}#_(t){let n=this.#n.failed,{reset:r,invoke_onerror:a}=this.#w(t);Ze(a),n&&(this.#s=Ue(()=>{n(this.#e,()=>t,()=>r)}))}#w(t){var n=!1,r=!1;let a=()=>{if(n){hs();return}n=!0,r&&rs(),this.#s!==null&&cn(this.#s,()=>{this.#s=null}),this.#v(()=>{this.#d()})};return{reset:a,invoke_onerror:()=>{try{r=!0,this.#n.onerror?.(t,a),r=!1}catch(l){Je(l,this.#a&&this.#a.parent)}}}}#k(){let t=this.#n.pending;t&&(this.is_pending=!0,this.#r=Ue(()=>t(this.#e)),Ze(()=>{var n=this.#i=document.createDocumentFragment(),r=Be(),a=!1;if(n.append(r),this.#o=this.#v(()=>{try{return Ue(()=>this.#l(r))}catch(i){try{this.error(i),a=!0}catch(l){Je(l,this.#a.parent)}return null}}),this.#o===null){this.#i=null,a&&this.#b(R);return}this.#c===0&&(this.#e.before(n),this.#i=null,cn(this.#r,()=>{this.#r=null}),this.#b(R))}))}#d(){try{if(this.is_pending=this.has_pending_snippet(),this.#c=0,this.#p=0,this.#o=Ue(()=>{this.#l(this.#e)}),this.#c>0){var t=this.#i=document.createDocumentFragment();Si(this.#o,t);let n=this.#n.pending;this.#r=Ue(()=>n(this.#e))}else this.#b(R)}catch(n){this.error(n)}}#b(t){this.is_pending=!1,t.transfer_effects(this.#h,this.#g)}defer_effect(t){Za(t,this.#h,this.#g)}is_rendered(){return!this.is_pending&&(!this.parent||this.parent.is_rendered())}has_pending_snippet(){return!!this.#n.pending}#v(t){var n=$,r=O,a=be;rt(this.#a),De(this.#a),jt(this.#a.ctx);try{return gt.ensure(),t()}finally{rt(n),De(r),jt(a)}}#x(t,n){if(!this.has_pending_snippet()){this.parent&&this.parent.#x(t,n);return}this.#c+=t,this.#c===0&&(this.#b(n),this.#r&&cn(this.#r,()=>{this.#r=null}),this.#i&&(this.#e.before(this.#i),this.#i=null))}update_pending_count(t,n){this.#x(t,n),this.#p+=t,!(!this.#f||this.#u)&&(this.#u=!0,Ze(()=>{this.#u=!1,this.#f&&Mn(this.#f,this.#p)}))}get_effect_pending(){return this.#y(),o(this.#f)}error(t){if(!this.#n.onerror&&!this.#n.failed)throw t;R?.is_fork?(this.#o&&R.skip_effect(this.#o),this.#r&&R.skip_effect(this.#r),this.#s&&R.skip_effect(this.#s),R.oncommit(()=>{this.#E(t)})):this.#E(t)}#E(t){this.#o&&(pe(this.#o),this.#o=null),this.#r&&(pe(this.#r),this.#r=null),this.#s&&(pe(this.#s),this.#s=null),T&&(we(this.#t),Mr(),we(Dr()));let n=this.#n.failed,r=a=>{let{reset:i,invoke_onerror:l}=this.#w(a);l(),n&&(this.#s=this.#v(()=>{try{return Ue(()=>{var s=$;s.b=this,s.f|=hr,n(this.#e,()=>a,()=>i)})}catch(s){return Je(s,this.#a.parent),null}}))};Ze(()=>{var a;try{a=this.transform_error(t)}catch(i){Je(i,this.#a&&this.#a.parent);return}a!==null&&typeof a=="object"&&typeof a.then=="function"?a.then(r,i=>Je(i,this.#a&&this.#a.parent)):r(a)})}};function Xe(e,t){var n=t==null?"":typeof t=="object"?`${t}`:t;n!==(e[pr]??=e.nodeValue)&&(e[pr]=n,e.nodeValue=`${n}`)}function $i(e,t){return Ri(e,t)}function Js(e,t){mr(),t.intro=t.intro??!1;let n=t.target,r=T,a=M;try{for(var i=Ee(n);i&&(i.nodeType!==vn||i.data!==Lr);)i=at(i);if(!i)throw At;Qe(!0),we(i);let l=Ri(e,{...t,anchor:i});return Qe(!1),l}catch(l){if(l instanceof Error&&l.message.split(`
`).some(s=>s.startsWith("https://svelte.dev/e/")))throw l;return l!==At&&console.warn("Failed to hydrate: ",l),t.recover===!1&&Qo(),mr(),ds(n),Qe(!1),$i(e,t)}finally{Qe(r),we(a)}}var Sn=new Map;function Ri(e,{target:t,anchor:n,props:r={},events:a,context:i,intro:l=!0,transformError:s}){mr();var f=void 0,c=Os(()=>{var h=n??t.appendChild(Be());Xs(h,{pending:()=>{}},g=>{ct({});var m=be;if(i&&(m.c=i),a&&(r.$$events=a),T&&Re(g,null),f=e(g,r)||Nr(),T&&($.nodes.end=M,M===null||M.nodeType!==vn||M.data!==ja))throw pn(),At;ut()},s);var d=new Set,p=g=>{for(var m=0;m<g.length;m++){var x=g[m];if(!d.has(x)){d.add(x);var P=qs(x);for(let ne of[t,document]){var B=Sn.get(ne);B===void 0&&(B=new Map,Sn.set(ne,B));var V=B.get(x);V===void 0?(ne.addEventListener(x,kr,{passive:P}),B.set(x,1)):B.set(x,V+1)}}}};return p(Do(Ci)),wr.add(p),()=>{for(var g of d)for(let P of[t,document]){var m=Sn.get(P),x=m.get(g);--x==0?(P.removeEventListener(g,kr),m.delete(g),m.size===0&&Sn.delete(P)):m.set(g,x)}wr.delete(p),h!==n&&h.parentNode?.removeChild(h)}});return Er.set(f,c),f}var Er=new WeakMap;function Zs(e,t){let n=Er.get(e);return n?(Er.delete(e),n(t)):Promise.resolve()}var Bt=class{anchor;#e=new Map;#t=new Map;#n=new Map;#l=new Set;#a=!0;constructor(t,n=!0){this.anchor=t,this.#a=n}#o=t=>{if(this.#e.has(t)){var n=this.#e.get(t),r=this.#t.get(n);if(r)Ea(r),this.#l.delete(n);else{var a=this.#n.get(n);a&&(Ea(a.effect),this.#t.set(n,a.effect),this.#n.delete(n),a.fragment.lastChild.remove(),this.anchor.before(a.fragment),r=a.effect)}for(let[i,l]of this.#e){if(this.#e.delete(i),i===t)break;let s=this.#n.get(l);s&&(pe(s.effect),this.#n.delete(l))}for(let[i,l]of this.#t){if(i===n||this.#l.has(i))continue;let s=()=>{if(Array.from(this.#e.values()).includes(i)){var c=document.createDocumentFragment();Si(l,c),c.append(Be()),this.#n.set(i,{effect:l,fragment:c})}else pe(l);this.#l.delete(i),this.#t.delete(i)};this.#a||!r?(this.#l.add(i),cn(l,s,!1)):s()}}};#r=t=>{this.#e.delete(t);let n=Array.from(this.#e.values());for(let[r,a]of this.#n)n.includes(r)||(pe(a.effect),this.#n.delete(r))};ensure(t,n){var r=R,a=vs();if(n&&!this.#t.has(t)&&!this.#n.has(t))if(a){var i=document.createDocumentFragment(),l=Be();i.append(l),this.#n.set(t,{effect:Ue(()=>n(l)),fragment:i})}else this.#t.set(t,Ue(()=>n(this.anchor)));if(this.#e.set(r,t),a){for(let[s,f]of this.#t)s===t?r.unskip_effect(f):r.skip_effect(f);for(let[s,f]of this.#n)s===t?r.unskip_effect(f.effect):r.skip_effect(f.effect);r.oncommit(this.#o),r.ondiscard(this.#r)}else T&&(this.anchor=M),this.#o(r)}};function Qs(e,t,...n){var r=new Bt(e);yn(()=>{let a=t()??null;r.ensure(a,a&&(i=>a(i,...n)))},pt)}function qr(e){be===null&&Go(),xe(()=>{let t=bn(e);if(typeof t=="function")return t})}function oe(e,t,n=!1){var r;T&&(r=M,Tt());var a=new Bt(e),i=n?pt:0;function l(s,f){if(T){var c=Ya(r);if(s!==parseInt(c.substring(1))){var h=Dr();we(h),a.anchor=h,Qe(!1),a.ensure(s,f),Qe(!0);return}}a.ensure(s,f)}yn(()=>{var s=!1;t((f,c=0)=>{s=!0,l(c,f)}),s||l(-1,null)},i)}var el=Symbol("NaN");function tl(e,t,n){T&&Tt();var r=new Bt(e);yn(()=>{var a=t();a!==a&&(a=el),r.ensure(a,n)})}function Ii(e,t,n=!1,r=!1,a=!1,i=!1){var l=e,s="";if(n){var f=e;T&&(l=we(Ee(f)))}me(()=>{var c=$;if(s===(s=t()??"")){T&&Tt();return}if(n&&!T){c.nodes=null,f.innerHTML=s,s!==""&&Re(Ee(f),f.lastChild);return}if(c.nodes!==null&&(wi(c.nodes.start,c.nodes.end),c.nodes=null),s!==""){if(T){M.data;for(var h=Tt(),d=h;h!==null&&(h.nodeType!==vn||h.data!=="");)d=h,h=at(h);if(h===null)throw pn(),At;Re(M,d),l=we(h);return}var p=r?os:a?ss:void 0,g=Ur(r?"svg":a?"math":"template",p);g.innerHTML=s;var m=r||a?g:g.content;if(Re(Ee(m),m.lastChild),r||a)for(;Ee(m);)l.before(Ee(m));else l.before(m)}})}function nl(e,t,n){var r;T&&(r=M,Tt());var a=new Bt(e);yn(()=>{var i=t()??null;if(T){var l=Ya(r),s=l===Lr,f=i!==null;if(s!==f){var c=Dr();we(c),a.anchor=c,Qe(!1),a.ensure(i,i&&(h=>n(h,i))),Qe(!0);return}}a.ensure(i,i&&(h=>n(h,i)))},pt)}function rl(e,t){var n=void 0,r;yi(()=>{n!==(n=t())&&(r&&(pe(r),r=null),n&&(r=Ue(()=>{Kr(()=>n(e))})))})}function Pi(e){var t,n,r="";if(typeof e=="string"||typeof e=="number")r+=e;else if(typeof e=="object")if(Array.isArray(e)){var a=e.length;for(t=0;t<a;t++)e[t]&&(n=Pi(e[t]))&&(r&&(r+=" "),r+=n)}else for(n in e)e[n]&&(r&&(r+=" "),r+=n);return r}function al(){for(var e,t,n=0,r="",a=arguments.length;n<a;n++)(e=arguments[n])&&(t=Pi(e))&&(r&&(r+=" "),r+=t);return r}function il(e){return typeof e=="object"?al(e):e??""}var Aa=[...` 	
\r\f\xA0\v\uFEFF`];function ol(e,t,n){var r=e==null?"":""+e;if(n){for(var a of Object.keys(n))if(n[a])r=r?r+" "+a:a;else if(r.length)for(var i=a.length,l=0;(l=r.indexOf(a,l))>=0;){var s=l+i;(l===0||Aa.includes(r[l-1]))&&(s===r.length||Aa.includes(r[s]))?r=(l===0?"":r.substring(0,l))+r.substring(s+1):l=s}}return r===""?null:r}function Ta(e,t=!1){var n=t?" !important;":";",r="";for(var a of Object.keys(e)){var i=e[a];i!=null&&i!==""&&(r+=" "+a+": "+i+n)}return r}function sr(e){return e[0]!=="-"||e[1]!=="-"?e.toLowerCase():e}function sl(e,t){if(t){var n="",r,a;if(Array.isArray(t)?(r=t[0],a=t[1]):r=t,e){e=String(e).replaceAll(/\/\*.*?\*\//g,"").trim();var i=!1,l=0,s=!1,f=[];r&&f.push(...Object.keys(r).map(sr)),a&&f.push(...Object.keys(a).map(sr));var c=0,h=-1;let x=e.length;for(var d=0;d<x;d++){var p=e[d];if(s?p==="/"&&e[d-1]==="*"&&(s=!1):i?i===p&&(i=!1):p==="/"&&e[d+1]==="*"?s=!0:p==='"'||p==="'"?i=p:p==="("?l++:p===")"&&l--,!s&&i===!1&&l===0){if(p===":"&&h===-1)h=d;else if(p===";"||d===x-1){if(h!==-1){var g=sr(e.substring(c,h).trim());if(!f.includes(g)){p!==";"&&d++;var m=e.substring(c,d).trim();n+=" "+m+";"}}c=d+1,h=-1}}}}return r&&(n+=Ta(r)),a&&(n+=Ta(a,!0)),n=n.trim(),n===""?null:n}return e==null?null:String(e)}function ll(e,t,n,r,a,i){var l=e[dr];if(T||l!==n||l===void 0){var s=ol(n,r,i);(!T||s!==e.getAttribute("class"))&&(s==null?e.removeAttribute("class"):t?e.className=s:e.setAttribute("class",s)),e[dr]=n}else if(i&&a!==i)for(var f in i){var c=!!i[f];(a==null||c!==!!a[f])&&e.classList.toggle(f,c)}return i}function lr(e,t={},n,r){for(var a in n){var i=n[a];t[a]!==i&&(n[a]==null?e.style.removeProperty(a):e.style.setProperty(a,i,r))}}function cl(e,t,n,r){var a=e[vr];if(T||a!==t){var i=sl(t,r);(!T||i!==e.getAttribute("style"))&&(i==null?e.removeAttribute("style"):e.style.cssText=i),e[vr]=t}else r&&(Array.isArray(r)?(lr(e,n?.[0],r[0]),lr(e,n?.[1],r[1],"important")):lr(e,n,r));return r}function Oi(e,t){t?e.hasAttribute("selected")||e.setAttribute("selected",""):e.removeAttribute("selected")}function $a(e,t){var n=!("__defaultValue"in e);!n&&e.__defaultValue===t||(e.__defaultValue=t,Li(e,!n||"__value"in e))}function Li(e,t){var n=e.__defaultValue,r=e.multiple,a=r?n??[]:null;if(!(r&&!Ir(a))){var i=e.selectedIndex,l=t&&r?new Set(e.selectedOptions):null;for(var s of e.options){var f=Cr(s);Oi(s,r?a.includes(f):Ga(f,n))}if(t)if(l!==null)for(s of e.options){var c=l.has(s);s.selected!==c&&(s.selected=c)}else e.selectedIndex!==i&&(e.selectedIndex=i)}}function Sr(e,t,n=!1){if(e.multiple){if(t==null)return;if(!Ir(t))return fs();for(var r of e.options)r.selected=t.includes(Cr(r));return}for(r of e.options){var a=Cr(r);if(Ga(a,t)){r.selected=!0;return}}(!n||t!==void 0)&&(e.selectedIndex=-1)}function ul(e){var t=new MutationObserver(n=>{n.every(fl)||("__defaultValue"in e&&Li(e,!1),"__value"in e&&Sr(e,e.__value))});t.observe(e,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["value"]}),Hn(()=>{t.disconnect()})}function Cr(e){return"__value"in e?e.__value:e.value}function fl(e){if(e.target.closest("selectedcontent")!==null)return!0;if(e.type==="childList"){var t=[...e.addedNodes,...e.removedNodes];return t.length>0&&t.every(n=>n.nodeName==="SELECTEDCONTENT")}return!1}var en=Symbol("class"),tn=Symbol("style"),Ni=Symbol("is custom element"),Mi=Symbol("is html"),hl=hn?"link":"LINK",Ra=hn?"input":"INPUT",dl=hn?"option":"OPTION",Di=hn?"select":"SELECT",vl=hn?"progress":"PROGRESS";function Wr(e){if(T){var t=!1,n=()=>{if(!t){if(t=!0,e.hasAttribute("value")){var r=e.value;U(e,"value",null),e.value=r}if(e.hasAttribute("checked")){var a=e.checked;U(e,"checked",null),e.checked=a}}};e[on]=n,Ze(n),ei()}}function pl(e,t){var n=Xr(e);n.value===(n.value=t??void 0)||e.value===t&&(t!==0||e.nodeName!==vl)||(e.value=t??"")}function U(e,t,n,r){var a=Xr(e);T&&(a[t]=e.getAttribute(t),t==="src"||t==="srcset"||t==="href"&&e.nodeName===hl)||a[t]!==(a[t]=n)&&(t==="loading"&&(e[Ko]=n),n==null?e.removeAttribute(t):typeof n!="string"&&Ui(e).has(t)?e[t]=n:e.setAttribute(t,n))}function gl(e,t,n,r,a=!1,i=!1){T&&a&&e.nodeName===Ra&&("defaultValue"in n||"defaultChecked"in n||Wr(e));var l=Xr(e),s=l[Ni],f=!l[Mi];let c=T&&s;c&&Qe(!1);var h=t||{},d=e.nodeName===dl,p=e.nodeName===Di;for(var g in t)!(g in n)&&g[0]+g[1]!=="$$"&&(n[g]=null);n.class?n.class=il(n.class):n[en]&&(n.class=null),n[tn]&&(n.style??=null);var m=Ui(e);if(e.nodeName===Ra&&"type"in n&&("value"in n||"__value"in n)){var x=n.type;(x!==h.type||x===void 0&&e.hasAttribute("type"))&&(h.type=x,U(e,"type",x))}for(let k in n){let L=n[k];if(d&&k==="value"&&L==null){e.value=e.__value="",h[k]=L;continue}if(k==="class"){var P=e.namespaceURI==="http://www.w3.org/1999/xhtml";ll(e,P,L,r,t?.[en],n[en]),h[k]=L,h[en]=n[en];continue}if(k==="style"){cl(e,L,t?.[tn],n[tn]),h[k]=L,h[tn]=n[tn];continue}var B=h[k];if(!(L===B&&!(L===void 0&&e.hasAttribute(k)))){h[k]=L;var V=k[0]+k[1];if(V!=="$$")if(V==="on"){let Z={},F="$$"+k,D=k.slice(2);var ne=Bs(D);if(js(D)&&(D=D.slice(0,-7),Z.capture=!0),!ne&&B){if(L!=null)continue;e.removeEventListener(D,h[F],Z),h[F]=null}if(ne)Fn(D,e,L),jn([D]);else if(L!=null){let Ce=function(ge){h[k].call(this,ge)};var ie=Ce;h[F]=Ai(D,e,Ce,Z)}}else if(k==="style")U(e,k,L);else if(k==="autofocus")ys(e,!!L);else if(!s&&(k==="__value"||k==="value"&&L!=null))e.value=e.__value=L;else if(k==="selected"&&d)Oi(e,L);else{var z=k;f||(z=Ys(z));var Ye=z==="defaultValue"||z==="defaultChecked";if(p&&z==="defaultValue")continue;if(L==null&&!s&&!Ye)if(l[k]=null,z==="value"||z==="checked"){let Z=e,F=t===void 0;if(z==="value"){let D=Z.defaultValue;Z.removeAttribute(z),Z.defaultValue=D,Z.value=Z.__value=F?D:null}else{let D=Z.defaultChecked;Z.removeAttribute(z),Z.defaultChecked=D,Z.checked=F?D:!1}}else e.removeAttribute(k);else Ye||(s||typeof L!="string")&&m.has(z)?(e[z]=L,z in l&&(l[z]=re)):typeof L!="function"&&U(e,z,L)}}}return c&&Qe(!0),h}function zn(e,t,n=[],r=[],a=[],i,l=!1,s=!1){ti(a,n,r,f=>{var c=void 0,h={},d=e.nodeName===Di,p=!1;if(yi(()=>{var m=t(...f.map(o)),x=gl(e,c,m,i,l,s);if(p&&d){var P=e;"defaultValue"in m&&$a(P,m.defaultValue),"value"in m&&Sr(P,m.value)}for(let V of Object.getOwnPropertySymbols(h))m[V]||pe(h[V]);for(let V of Object.getOwnPropertySymbols(m)){var B=m[V];V.description===ls&&(!c||B!==c[V])&&(h[V]&&pe(h[V]),h[V]=Ue(()=>rl(e,()=>B))),x[V]=B}c=x}),d){var g=e;Kr(()=>{var m=c;"defaultValue"in m&&$a(g,m.defaultValue),Sr(g,m.value,!0),ul(g)})}p=!0})}function Xr(e){return e[Ua]??={[Ni]:e.nodeName.includes("-"),[Mi]:e.namespaceURI===za}}var Ia=new Map;function Ui(e){var t=e.getAttribute("is")||e.nodeName,n=Ia.get(t);if(n)return n;Ia.set(t,n=new Set);for(var r,a=e,i=Element.prototype;i!==a;){r=Uo(a);for(var l in r)r[l].set&&l!=="innerHTML"&&l!=="textContent"&&l!=="innerText"&&n.add(l);a=Na(a)}return n}function ml(e,t,n=t){var r=new WeakSet;_s(e,"input",async a=>{var i=a?e.defaultValue:e.value;if(i=cr(e)?ur(i):i,n(i),R!==null&&r.add(R),await xt(),i!==(i=t())){var l=e.selectionStart,s=e.selectionEnd,f=e.value.length;if(e.value=i??"",s!==null){var c=e.value.length;l===s&&s===f&&c>f?(e.selectionStart=c,e.selectionEnd=c):(e.selectionStart=l,e.selectionEnd=Math.min(s,c))}}}),(T&&e.defaultValue!==e.value||bn(t)==null&&e.value)&&(n(cr(e)?ur(e.value):e.value),R!==null&&r.add(R)),Vn(()=>{var a=t();if(e===document.activeElement){var i=R;if(r.has(i))return}cr(e)&&a===ur(e.value)||e.type==="date"&&!a&&!e.value||a!==e.value&&(e.value=a??"")})}function cr(e){var t=e.type;return t==="number"||t==="range"}function ur(e){return e===""?null:+e}function fr(e,t){return e===t||e?.[Ht]===t}function mt(e=Nr(),t,n,r){var a=be.r,i=$;return Kr(()=>{var l,s;return Vn(()=>{l=s,s=[],bn(()=>{fr(n(...s),e)||(t(e,...s),l&&fr(n(...l),e)&&t(null,...l))})}),()=>{let f=i;for(;f!==a&&f.parent!==null&&f.parent.f&In;)f=f.parent;let c=()=>{s&&fr(n(...s),e)&&t(null,...s)},h=f.teardown;f.teardown=()=>{c(),h?.()}}}),e}var bl={get(e,t){if(!e.exclude.has(t))return e.props[t]},set(e,t){return!1},getOwnPropertyDescriptor(e,t){if(!e.exclude.has(t)&&t in e.props)return{enumerable:!0,configurable:!0,value:e.props[t]}},has(e,t){return e.exclude.has(t)?!1:t in e.props},ownKeys(e){return Reflect.ownKeys(e.props).filter(t=>!e.exclude.has(t))}};function Bn(e,t,n){return new Proxy({props:e,exclude:t},bl)}function J(e,t,n,r){var a=r,i=!0,l=()=>(i&&(i=!1,a=r),a),s;s=e[t],s===void 0&&r!==void 0&&(s=l());var f;f=()=>{var p=e[t];return p===void 0?l():(i=!0,p)};var c=!1,h=Vr(()=>(c=!1,f())),d=$;return(function(p,g){if(arguments.length>0){let m=g?o(h):p;return _(h,m),c=!0,a!==void 0&&(a=m),p}return lt&&c||(d.f&$e)!==0?h.v:o(h)})}function yl(e){return new Ar(e)}var Ar=class{#e;#t;constructor(t){var n=new Map,r=(i,l)=>{var s=ci(l,!1,!1);return n.set(i,s),s};let a=new Proxy({...t.props||{},$$events:{}},{get(i,l){return o(n.get(l)??r(l,Reflect.get(i,l)))},has(i,l){return l===Bo?!0:(o(n.get(l)??r(l,Reflect.get(i,l))),Reflect.has(i,l))},set(i,l,s){return _(n.get(l)??r(l,s),s),Reflect.set(i,l,s)}});this.#t=(t.hydrate?Js:$i)(t.component,{target:t.target,anchor:t.anchor,props:a,context:t.context,intro:t.intro??!1,recover:t.recover,transformError:t.transformError}),(!t?.props?.$$host||t.sync===!1)&&Y(),this.#e=a.$$events;for(let i of Object.keys(this.#t))i==="$set"||i==="$destroy"||i==="$on"||Vt(this,i,{get(){return this.#t[i]},set(l){this.#t[i]=l},enumerable:!0});this.#t.$set=i=>{Object.assign(a,i)},this.#t.$destroy=()=>{Zs(this.#t)}}$set(t){this.#t.$set(t)}$on(t,n){this.#e[t]=this.#e[t]||[];let r=(...a)=>n.call(this,...a);return this.#e[t].push(r),()=>{this.#e[t]=this.#e[t].filter(a=>a!==r)}}$destroy(){this.#t.$destroy()}},Hi=class{};typeof HTMLElement=="function"&&(Hi=class extends HTMLElement{$$ctor;$$s;$$c;$$cn=!1;$$d={};$$r=!1;$$p_d={};$$l={};$$l_u=new Map;$$me;$$shadowRoot=null;constructor(e,t,n){super(),this.$$ctor=e,this.$$s=t,n&&(this.$$shadowRoot=this.attachShadow(n))}addEventListener(e,t,n){if(this.$$l[e]=this.$$l[e]||[],this.$$l[e].push(t),this.$$c){let r=this.$$c.$on(e,t);this.$$l_u.set(t,r)}super.addEventListener(e,t,n)}removeEventListener(e,t,n){if(super.removeEventListener(e,t,n),this.$$c){let r=this.$$l_u.get(t);r&&(r(),this.$$l_u.delete(t))}}async connectedCallback(){if(this.$$cn=!0,!this.$$c){let t=function(a){return i=>{let l=Ur("slot");a!=="default"&&(l.name=a),N(i,l)}};var e=t;if(await Promise.resolve(),!this.$$cn||this.$$c)return;let n={},r=_l(this);for(let a of this.$$s)a in r&&(a==="default"&&!this.$$d.children?(this.$$d.children=t(a),n.default=!0):n[a]=t(a));for(let a of this.attributes){let i=this.$$g_p(a.name);i in this.$$d||(this.$$d[i]=Tn(i,a.value,this.$$p_d,"toProp"))}for(let a in this.$$p_d)!(a in this.$$d)&&this[a]!==void 0&&(this.$$d[a]=this[a],delete this[a]);this.$$c=yl({component:this.$$ctor,target:this.$$shadowRoot||this,props:{...this.$$d,$$slots:n,$$host:this}}),this.$$me=Ps(()=>{Vn(()=>{this.$$r=!0;for(let a of Rn(this.$$c)){if(!this.$$p_d[a]?.reflect)continue;this.$$d[a]=this.$$c[a];let i=Tn(a,this.$$d[a],this.$$p_d,"toAttribute");i==null?this.removeAttribute(this.$$p_d[a].attribute||a):this.setAttribute(this.$$p_d[a].attribute||a,i)}this.$$r=!1})});for(let a in this.$$l)for(let i of this.$$l[a]){let l=this.$$c.$on(a,i);this.$$l_u.set(i,l)}this.$$l={}}}attributeChangedCallback(e,t,n){this.$$r||(e=this.$$g_p(e),this.$$d[e]=Tn(e,n,this.$$p_d,"toProp"),this.$$c?.$set({[e]:this.$$d[e]}))}disconnectedCallback(){this.$$cn=!1,Promise.resolve().then(()=>{!this.$$cn&&this.$$c&&(this.$$c.$destroy(),this.$$me(),this.$$c=void 0)})}$$g_p(e){return Rn(this.$$p_d).find(t=>this.$$p_d[t].attribute===e||!this.$$p_d[t].attribute&&t.toLowerCase()===e)||e}});function Tn(e,t,n,r){let a=n[e]?.type;if(t=a==="Boolean"&&typeof t!="boolean"?t!=null:t,!r||!n[e])return t;if(r==="toAttribute")switch(a){case"Object":case"Array":return t==null?null:JSON.stringify(t);case"Boolean":return t?"":null;case"Number":return t??null;default:return t}else switch(a){case"Object":case"Array":return t&&JSON.parse(t);case"Boolean":return t;case"Number":return t!=null?+t:t;default:return t}}function _l(e){let t={};return e.childNodes.forEach(n=>{t[n.slot||"default"]=!0}),t}function bt(e,t,n,r,a,i){let l=class extends Hi{constructor(){super(e,n,a),this.$$p_d=t}static get observedAttributes(){return Rn(t).map(s=>(t[s].attribute||s).toLowerCase())}};return Rn(t).forEach(s=>{Vt(l.prototype,s,{get(){return this.$$c&&s in this.$$c?this.$$c[s]:this.$$d[s]},set(f){f=Tn(s,f,t),this.$$d[s]=f;var c=this.$$c;if(c){var h=Dt(c,s)?.get;h?c[s]=f:c.$set({[s]:f})}}})}),r.forEach(s=>{Vt(l.prototype,s,{get(){return this.$$c?.[s]}})}),e.element=l,l}var wl=new Set(["$$slots","$$events","$$legacy","$$host","loading"]),kl=ee('<div class="altcha-checkbox"><input/> <svg aria-hidden="true" width="12" height="9" viewBox="0 0 12 9"><polyline points="1 5 4 8 11 1"></polyline></svg> <div class="altcha-spinner altcha-checkbox-spinner" aria-hidden="true"></div></div>');function Vi(e,t){ct(t,!0);let n=J(t,"loading"),r=Bn(t,wl),a;function i(){a?.click()}var l={get loading(){return n()},set loading(h){n(h),Y()}},s=kl(),f=he(s);zn(f,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),mt(f,h=>a=h,()=>a);var c=X(f,2);return Mr(2),ae(s),me(()=>U(s,"data-loading",n())),Fn("click",c,i),N(e,s),ut(l)}jn(["click"]);bt(Vi,{loading:{}},[],[],{mode:"open"});var xl=new Set(["$$slots","$$events","$$legacy","$$host","loading"]),El=ee('<div class="altcha-checkbox-native"><input/> <div class="altcha-spinner altcha-checkbox-native-spinner"></div></div>');function Fi(e,t){ct(t,!0);let n=J(t,"loading"),r=Bn(t,xl);var a={get loading(){return n()},set loading(s){n(s),Y()}},i=El(),l=he(i);return zn(l,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),Mr(2),ae(i),me(()=>U(i,"data-loading",n())),N(e,i),ut(a)}bt(Fi,{loading:{}},[],[],{mode:"open"});var Sl=ee('<div><a target="_blank" rel="noopener" class="altcha-logo" aria-hidden="true" tabindex="-1"><svg width="22" height="22" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.33955 16.4279C5.88954 20.6586 12.1971 21.2105 16.4279 17.6604C18.4699 15.947 19.6548 13.5911 19.9352 11.1365L17.9886 10.4279C17.8738 12.5624 16.909 14.6459 15.1423 16.1284C11.7577 18.9684 6.71167 18.5269 3.87164 15.1423C1.03163 11.7577 1.4731 6.71166 4.8577 3.87164C8.24231 1.03162 13.2883 1.4731 16.1284 4.8577C16.9767 5.86872 17.5322 7.02798 17.804 8.2324L19.9522 9.01429C19.7622 7.07737 19.0059 5.17558 17.6604 3.57212C14.1104 -0.658624 7.80283 -1.21043 3.57212 2.33956C-0.658625 5.88958 -1.21046 12.1971 2.33955 16.4279Z" fill="currentColor"></path><path d="M3.57212 2.33956C1.65755 3.94607 0.496389 6.11731 0.12782 8.40523L2.04639 9.13961C2.26047 7.15832 3.21057 5.25375 4.8577 3.87164C8.24231 1.03162 13.2883 1.4731 16.1284 4.8577L13.8302 6.78606L19.9633 9.13364C19.7929 7.15555 19.0335 5.20847 17.6604 3.57212C14.1104 -0.658624 7.80283 -1.21043 3.57212 2.33956Z" fill="currentColor"></path><path d="M7 10H5C5 12.7614 7.23858 15 10 15C12.7614 15 15 12.7614 15 10H13C13 11.6569 11.6569 13 10 13C8.3431 13 7 11.6569 7 10Z" fill="currentColor"></path></svg></a></div>');function Jr(e,t){ct(t,!0);let n=J(t,"strings"),r="https://altcha.org";var a={get strings(){return n()},set strings(s){n(s),Y()}},i=Sl(),l=he(i);return U(l,"href",r),ae(i),me(()=>U(l,"aria-label",n().ariaLinkLabel)),N(e,i),ut(a)}bt(Jr,{strings:{}},[],[],{mode:"open"});var Cl=ee('<div class="altcha-footer"><p></p> <!></div>');function Tr(e,t){ct(t,!0);let n=J(t,"logo"),r=J(t,"strings");var a={get logo(){return n()},set logo(c){n(c),Y()},get strings(){return r()},set strings(c){r(c),Y()}},i=Cl(),l=he(i);Ii(l,()=>r().footer,!0),ae(l);var s=X(l,2);{var f=c=>{Jr(c,{get strings(){return r()}})};oe(s,c=>{n()&&c(f)})}return ae(i),N(e,i),ut(a)}bt(Tr,{logo:{},strings:{}},[],[],{mode:"open"});var Al=new Set(["$$slots","$$events","$$legacy","$$host","loading"]),Tl=ee('<div class="altcha-switch"><input/>  <div class="altcha-switch-toggle"><div class="altcha-spinner altcha-switch-spinner"></div></div></div>');function ji(e,t){ct(t,!0);let n=J(t,"loading"),r=Bn(t,Al),a;function i(){a?.click()}var l={get loading(){return n()},set loading(h){n(h),Y()}},s=Tl(),f=he(s);zn(f,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),mt(f,h=>a=h,()=>a);var c=X(f,2);return ae(s),me(()=>U(s,"data-loading",n())),Fn("click",c,i),N(e,s),ut(l)}jn(["click"]);bt(ji,{loading:{}},[],[],{mode:"open"});var ve=(e=>(e.ERROR="error",e.LOADING="loading",e.PLAYING="playing",e.PAUSED="paused",e.READY="ready",e))(ve||{}),zi=(e=>(e.SHA_256="SHA-256",e.SHA_384="SHA-384",e.SHA_512="SHA-512",e))(zi||{}),H=(e=>(e.CODE="code",e.ERROR="error",e.VERIFIED="verified",e.VERIFYING="verifying",e.UNVERIFIED="unverified",e.EXPIRED="expired",e))(H||{}),$l=ee('<div class="altcha-code-challenge-title"> </div>'),Rl=ee('<div class="altcha-spinner"></div>'),Il=Gr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.8659 3.00017L22.3922 19.5002C22.6684 19.9785 22.5045 20.5901 22.0262 20.8662C21.8742 20.954 21.7017 21.0002 21.5262 21.0002H2.47363C1.92135 21.0002 1.47363 20.5525 1.47363 20.0002C1.47363 19.8246 1.51984 19.6522 1.60761 19.5002L11.1339 3.00017C11.41 2.52187 12.0216 2.358 12.4999 2.63414C12.6519 2.72191 12.7782 2.84815 12.8659 3.00017ZM10.9999 16.0002V18.0002H12.9999V16.0002H10.9999ZM10.9999 9.00017V14.0002H12.9999V9.00017H10.9999Z"></path></svg>'),Pl=Gr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M15 7C15 6.44772 15.4477 6 16 6C16.5523 6 17 6.44772 17 7V17C17 17.5523 16.5523 18 16 18C15.4477 18 15 17.5523 15 17V7ZM7 7C7 6.44772 7.44772 6 8 6C8.55228 6 9 6.44772 9 7V17C9 17.5523 8.55228 18 8 18C7.44772 18 7 17.5523 7 17V7Z"></path></svg>'),Ol=Gr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 12H7C8.10457 12 9 12.8954 9 14V19C9 20.1046 8.10457 21 7 21H4C2.89543 21 2 20.1046 2 19V12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12V19C22 20.1046 21.1046 21 20 21H17C15.8954 21 15 20.1046 15 19V14C15 12.8954 15.8954 12 17 12H20C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12Z"></path></svg>'),Ll=ee('<button type="button" class="altcha-button altcha-button-secondary"><!></button>'),Nl=ee('<audio hidden="" autoplay=""></audio>'),Ml=ee('<div class="altcha-code-challenge"><form data-code-challenge="true"><!> <div class="altcha-code-challenge-text"> </div> <img class="altcha-code-challenge-image" alt=""/> <div class="altcha-code-challenge-row"><input type="text" class="altcha-input" autocomplete="off" name="" required=""/> <!> <button type="button" class="altcha-button altcha-button-secondary"><svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2V4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 9.25022 5.38734 6.82447 7.50024 5.38451L7.5 8H9.5V2L3.5 2V4L5.99918 3.99989C3.57075 5.82434 2 8.72873 2 12Z"></path></svg></button></div> <div class="altcha-code-challenge-buttons"><button type="submit" class="altcha-button"> </button> <button type="button" class="altcha-button altcha-button-secondary"> </button></div></form> <!></div>');function Bi(e,t){ct(t,!0);let n=J(t,"audioUrl"),r=J(t,"codeChallenge"),a=J(t,"config"),i=J(t,"imageUrl"),l=J(t,"onCancel"),s=J(t,"onReload"),f=J(t,"onSubmit"),c=J(t,"strings"),h=I(void 0),d=I(void 0),p=I(void 0),g=I(!1),m=I(""),x=I(!1);qr(()=>(a().disableAutoFocus||xt().then(()=>{o(p)?.focus()}),()=>{o(d)&&(o(d).pause(),_(d,void 0))}));function P(){_(h,ve.PAUSED,!0)}function B(S){_(h,ve.ERROR,!0)}function V(){_(h,ve.READY,!0)}function ne(){_(h,ve.LOADING,!0)}function ie(){_(h,ve.PLAYING,!0)}function z(){_(h,ve.PAUSED,!0)}function Ye(S){S.code==="Space"?(S.preventDefault(),S.stopPropagation(),Z()):S.code==="Escape"&&(S.preventDefault(),S.stopPropagation(),l()?.())}function k(S){S.preventDefault(),S.stopPropagation(),f()?.(o(m))}function L(S){S.play().catch(G=>{if(!(G instanceof DOMException&&G.name==="AbortError"))throw G})}function Z(){o(d)?o(h)===ve.LOADING||(o(d).paused?(n()&&o(d).src!==n()&&(o(d).src=n()),o(d).currentTime=0,L(o(d))):o(d).pause()):(_(x,!0),requestAnimationFrame(()=>{o(d)&&n()&&(o(d).src=n(),L(o(d)))}))}var F={get audioUrl(){return n()},set audioUrl(S){n(S),Y()},get codeChallenge(){return r()},set codeChallenge(S){r(S),Y()},get config(){return a()},set config(S){a(S),Y()},get imageUrl(){return i()},set imageUrl(S){i(S),Y()},get onCancel(){return l()},set onCancel(S){l(S),Y()},get onReload(){return s()},set onReload(S){s(S),Y()},get onSubmit(){return f()},set onSubmit(S){f(S),Y()},get strings(){return c()},set strings(S){c(S),Y()}},D=Ml(),Ce=he(D),ge=he(Ce);{var Ie=S=>{var G=$l(),_t=Nt(G,!0);me(()=>Xe(_t,c().verificationRequired)),N(S,G)};oe(ge,S=>{a().codeChallengeDisplay!=="standard"&&S(Ie)})}var W=X(ge,2),it=Nt(W,!0),E=X(W,2),ue=X(E,2),Q=he(ue);Wr(Q),Q.disabled=o(g),mt(Q,S=>_(p,S),()=>o(p));var b=X(Q,2);{var Gt=S=>{var G=Ll(),_t=he(G);{var Zt=ye=>{var qe=Rl();N(ye,qe)},Gn=ye=>{var qe=Il();N(ye,qe)},qn=ye=>{var qe=Pl();N(ye,qe)},Wn=ye=>{var qe=Ol();N(ye,qe)};oe(_t,ye=>{o(h)===ve.LOADING?ye(Zt):o(h)===ve.ERROR?ye(Gn,1):o(h)===ve.PLAYING?ye(qn,2):ye(Wn,-1)})}ae(G),me(()=>{U(G,"title",c().getAudioChallenge),G.disabled=o(h)===ve.LOADING||o(h)===ve.ERROR,U(G,"aria-label",o(h)===ve.LOADING?c().loading:c().getAudioChallenge)}),se("click",G,()=>Z(),!0),N(S,G)};oe(b,S=>{r().audio&&S(Gt)})}var qt=X(b,2);ae(ue);var Ge=X(ue,2),Wt=he(Ge),_n=Nt(Wt,!0),yt=X(Wt,2),Xt=Nt(yt,!0);ae(Ge),ae(Ce);var Jt=X(Ce,2);{var ke=S=>{var G=Nl();mt(G,_t=>_(d,_t),()=>o(d)),se("error",G,B),se("loadstart",G,ne),se("canplay",G,V),se("pause",G,z),se("playing",G,ie),se("ended",G,P),N(S,G)};oe(Jt,S=>{o(x)&&S(ke)})}return ae(D),me(()=>{Xe(it,c().enterCodeFromImage),U(E,"src",i()),U(Q,"minlength",r().length||1),U(Q,"maxlength",r().length),U(Q,"placeholder",c().enterCode),U(Q,"aria-label",o(h)===ve.LOADING?c().loading:o(h)===ve.PLAYING?"":c().enterCodeAria),U(Q,"aria-live",o(h)?"assertive":"polite"),U(Q,"aria-busy",o(h)===ve.LOADING),U(qt,"title",c().reload),U(qt,"aria-label",c().reload),U(Wt,"aria-label",c().verify),Xe(_n,c().verify),U(yt,"aria-label",c().cancel),Xe(Xt,c().cancel)}),se("submit",Ce,k,!0),Fn("keydown",Q,Ye),ml(Q,()=>o(m),S=>_(m,S)),se("click",qt,()=>s()?.(),!0),se("click",yt,()=>l()?.(),!0),N(e,D),ut(F)}jn(["keydown"]);bt(Bi,{audioUrl:{},codeChallenge:{},config:{},imageUrl:{},onCancel:{},onReload:{},onSubmit:{},strings:{}},[],[],{mode:"open"});var Dl=new Set(["$$slots","$$events","$$legacy","$$host","anchor","children","display","backdrop","onClickOutside","onClickOutsideDelay","onClose","placement","updateUISignal","variant"]),Ul=ee('<div class="altcha-popover-backdrop" data-backdrop=""></div>'),Hl=ee('<div class="altcha-popover-arrow"></div>'),Vl=ee('<div role="button" class="altcha-popover-close">&times;</div>'),Fl=ee('<!> <div><!> <!> <div class="altcha-popover-content"><!></div></div>',1);function $r(e,t){ct(t,!0);let n=J(t,"anchor"),r=J(t,"children"),a=J(t,"display",7,"standard"),i=J(t,"backdrop",7,!1),l=J(t,"onClickOutside"),s=J(t,"onClickOutsideDelay",7,600),f=J(t,"onClose"),c=J(t,"placement",7,"auto"),h=J(t,"updateUISignal"),d=J(t,"variant",7,"neutral"),p=Bn(t,Dl),g=I(void 0),m=I(void 0),x=I(!1),P=I(0);xe(()=>{c()!=="auto"&&_(x,c()==="top")}),xe(()=>{h()&&z()}),qr(()=>{let E=a()==="bottomsheet"||a()==="overlay";return E&&(o(m)&&document.body.append(o(m)),o(g)&&document.body.append(o(g))),z(),xt().then(()=>{_(P,Date.now(),!0)}),()=>{E&&(o(m)&&document.body.removeChild(o(m)),o(g)&&document.body.removeChild(o(g)))}});function B(){f()?.()}function V(E){let ue=E.target;!o(g)?.contains(ue)&&(!s()||o(P)+s()<Date.now())&&l()?.()}function ne(){z()}function ie(){z()}function z(){if(n()&&c()==="auto"&&o(g)){let E=n().getBoundingClientRect(),Q=document.documentElement.clientHeight-(E.top+E.height)<o(g).clientHeight;o(x)!==Q&&_(x,Q)}}var Ye={get anchor(){return n()},set anchor(E){n(E),Y()},get children(){return r()},set children(E){r(E),Y()},get display(){return a()},set display(E="standard"){a(E),Y()},get backdrop(){return i()},set backdrop(E=!1){i(E),Y()},get onClickOutside(){return l()},set onClickOutside(E){l(E),Y()},get onClickOutsideDelay(){return s()},set onClickOutsideDelay(E=600){s(E),Y()},get onClose(){return f()},set onClose(E){f(E),Y()},get placement(){return c()},set placement(E="auto"){c(E),Y()},get updateUISignal(){return h()},set updateUISignal(E){h(E),Y()},get variant(){return d()},set variant(E="neutral"){d(E),Y()}},k=Fl();se("click",Et,V,!0),se("resize",Et,ne),se("scroll",Et,ie);var L=Lt(k);{var Z=E=>{var ue=Ul();mt(ue,Q=>_(m,Q),()=>o(m)),N(E,ue)};oe(L,E=>{i()&&E(Z)})}var F=X(L,2);zn(F,()=>({...p,class:`altcha-popover ${(t.class||"")??""}`,"data-popover":!0,"data-variant":d(),"data-top":o(x),"data-display":a()}));var D=he(F);{var Ce=E=>{var ue=Hl();N(E,ue)};oe(D,E=>{a()==="standard"&&E(Ce)})}var ge=X(D,2);{var Ie=E=>{var ue=Vl();se("click",ue,B,!0),N(E,ue)};oe(ge,E=>{a()!=="standard"&&E(Ie)})}var W=X(ge,2),it=he(W);return Qs(it,()=>r()??dt),ae(W),ae(F),mt(F,E=>_(g,E),()=>o(g)),N(e,k),ut(Ye)}bt($r,{anchor:{},children:{},display:{},backdrop:{},onClickOutside:{},onClickOutsideDelay:{},onClose:{},placement:{},updateUISignal:{},variant:{}},[],[],{mode:"open"});function jl(e){return Array.from(new Uint8Array(e)).map(t=>t.toString(16).padStart(2,"0")).join("")}function zl(e,t="altcha-css",n){if(typeof document<"u"&&document&&!document.getElementById(t)){let r=document.createElement("style");r.id=t,r.textContent=e;let a=document.currentScript?.nonce??document.querySelector('meta[name="csp-nonce"]')?.content;a&&(r.nonce=a),document.head.appendChild(r)}}Object.values(zi);async function Ki(e){let{challenge:t,concurrency:n=navigator.hardwareConcurrency,controller:r=new AbortController,createWorker:a,onOutOfMemory:i=p=>p>1?Math.floor(p/2):0,counterMode:l,timeout:s}=e,f=Math.min(16,Math.max(1,n)),c=[],h=()=>{for(let p of c)p.terminate()};for(let p=0;p<f;p++)c.push(await a(t.parameters.algorithm));let d=null;try{d=await Promise.race(c.map((p,g)=>(r.signal.addEventListener("abort",()=>{p.postMessage({type:"abort"})}),new Promise((m,x)=>{p.addEventListener("error",P=>{x(P)}),p.addEventListener("message",P=>{if(P.data){for(let B of c)B!==p&&B.postMessage({type:"abort"});if(P.data.error)return x(new Error(P.data.error))}m(P.data)}),p.postMessage({challenge:t,counterMode:l,counterStart:g,counterStep:f,timeout:s,type:"work"})}))))}catch(p){if(p instanceof Error&&!!p?.message?.includes("Out of memory")&&i){h();let m=i(f);if(m)return Ki({...e,challenge:t,controller:r,concurrency:m,createWorker:a})}throw p}finally{h()}return r.signal.aborted?null:d||null}var Rr=class{TAG_CODES={INPUT:1,TEXTAREA:2,SELECT:3,BUTTON:4,A:5,DETAILS:6,SUMMARY:7,IFRAME:8,VIDEO:9,AUDIO:10};maxSamples;sampleInterval;target;focusStartTime=0;focusInteraction=0;focusInteractionTimer=null;lastPointerSample=0;lastTouchSample=0;lastScrollSample=0;pendingPointer=null;pendingTouch=null;focus=[];pointer=[];scroll=[];touch=[];constructor(t={}){let{maxSamples:n=60,sampleInterval:r=50,target:a=window}=t;this.maxSamples=n,this.sampleInterval=r,this.target=a,this.attach()}destroy(){let t={capture:!0};this.target.removeEventListener("focusin",this.onFocus,t),this.target.removeEventListener("keydown",this.onInteraction,t),this.target.removeEventListener("pointerdown",this.onInteraction,t),this.target.removeEventListener("pointermove",this.onPointer,t),this.target.removeEventListener("scroll",this.onScroll,t),this.target.removeEventListener("touchmove",this.onTouchMove,t)}export(){return{focus:this.focus,maxTouchPoints:navigator.maxTouchPoints||0,pointer:this.pointer,scroll:this.scroll,time:Date.now(),touch:this.touch}}attach(){let t={passive:!0,capture:!0};this.target.addEventListener("focusin",this.onFocus,t),this.target.addEventListener("keydown",this.onInteraction,t),this.target.addEventListener("pointerdown",this.onInteraction,t),this.target.addEventListener("pointermove",this.onPointer,t),this.target.addEventListener("scroll",this.onScroll,t),this.target.addEventListener("touchmove",this.onTouchMove,t)}evict(t){t.length>this.maxSamples&&t.splice(0,t.length-this.maxSamples)}onFocus=t=>{if(this.focusInteraction===2)return;let n=t.target;if(!(n instanceof Element))return;let r=performance.now();this.focusStartTime===0&&(this.focusStartTime=r),this.focus.push([Math.round(r-this.focusStartTime),n.tabIndex,this.TAG_CODES[n.tagName]??0,this.focusInteraction?1:0]),this.evict(this.focus)};onInteraction=t=>{this.focusInteraction="keyCode"in t?1:2,this.focusInteractionTimer&&clearTimeout(this.focusInteractionTimer),this.focusInteractionTimer=setTimeout(()=>{this.focusInteraction=0},100)};onPointer=t=>{if(t.pointerType==="touch")return;let n=t.timeStamp||performance.now();this.pendingPointer=[Math.round(t.clientX),Math.round(t.clientY),Math.round(n)],n-this.lastPointerSample>=this.sampleInterval&&(this.pointer.push(this.pendingPointer),this.lastPointerSample=n,this.pendingPointer=null,this.evict(this.pointer))};onScroll=()=>{let t=performance.now();t-this.lastScrollSample<this.sampleInterval||(this.scroll.push([Math.round(window.scrollY),Math.round(t)]),this.lastScrollSample=t,this.evict(this.scroll))};onTouchMove=t=>{let n=t.timeStamp||performance.now(),r=t.touches[0];r&&(this.pendingTouch=[Math.round(r.clientX),Math.round(r.clientY),Math.round(n),Math.round(r.force*1e3)/1e3,Math.round(r.radiusX||0),Math.round(r.radiusY||0)],n-this.lastTouchSample>=this.sampleInterval&&(this.touch.push(this.pendingTouch),this.lastTouchSample=n,this.pendingTouch=null,this.evict(this.touch)))}},Bl=ee('<div class="altcha-overlay-backdrop" data-backdrop=""></div>'),Kl=ee('<div class="altcha-overlay-content"></div>'),Yl=ee('<div role="button" class="altcha-overlay-close">&times;</div> <!>',1),Gl=ee('<div class="altcha-floating-arrow"></div>'),ql=ee('<input type="hidden"/>'),Wl=ee('<div class="altcha-error">Secure context (HTTPS) required.</div>'),Pa=ee('<div class="altcha-error"> </div>'),Xl=ee("<!> <!>",1),Jl=ee('<!> <div class="altcha"><!> <div class="altcha-main"><div><div class="altcha-checkbox-wrap"><!> <label><!></label></div> <!></div> <!> <!> <!></div> <!></div>',1);function Zl(e,t){ct(t,!0);let n=()=>ma(h,"$altchaDefaults",a),r=()=>ma(m,"$altchaI18nStore",a),[a,i]=bs(),l='input[type="text"]:not([data-no-spamfilter]), textarea:not([data-no-spamfilter])',s='input[type="submit"], button[type="submit"], button:not([type="button"]):not([type="reset"])',f=["ar","fa","he","ur"],{isSecureContext:c}=globalThis,{store:h}=globalThis.$altcha.defaults,d=navigator.hardwareConcurrency||2,p=navigator.deviceMemory||0,g=p&&p<=4?Math.min(4,d):d,m=globalThis.$altcha.i18n.store,x=t.$$host,P=(u,v)=>{xt().then(()=>{x?.dispatchEvent(new CustomEvent(u,{detail:v}))})},B=null,V=I(st(new URL(location.origin))),ne=I(!1),ie=I(null),z=I(null),Ye=I(null),k=I(st(H.UNVERIFIED)),L=I(void 0),Z=I(void 0),F=I(null),D=I(void 0),Ce=I(null),ge=I(null),Ie=I(null),W=I(null),it=I(st([])),E=I(0),ue=I(st({})),Q=I(!0),b=_e(()=>({fetch:(u,v)=>fetch(u,v),audioChallengeLanguage:"",auto:"off",barPlacement:"bottom",challenge:"",codeChallenge:null,codeChallengeDisplay:"standard",credentials:null,debug:!1,disableAutoFocus:!1,display:"standard",floatingAnchor:"",floatingOffset:8,floatingPersist:!1,floatingPlacement:"auto",hideFooter:!1,hideLogo:!1,humanInteractionSignature:!0,language:"",mockError:!1,minDuration:500,overlayContent:"",name:"altcha",popoverPlacement:"auto",retryOnOutOfMemoryError:!0,setCookie:null,serverVerificationFields:!1,serverVerificationTimeZone:!1,test:!1,timeout:9e4,type:"checkbox",validationMessage:"",verifyFunction:null,verifyUrl:"",workers:g,...n(),...o(ue)})),Gt=_e(()=>`altcha-checkbox-${t.id||Math.floor(Math.random()*1e12).toString(16)}`),qt=_e(()=>Ji(o(b).type)),Ge=_e(()=>o(b).auto),Wt=_e(()=>o(k)===H.VERIFYING),_n=_e(()=>!o(b).hideFooter),yt=_e(()=>!o(b).hideLogo&&o(b).display!=="bar"),Xt=_e(()=>Zi(r(),[o(b).language,document.documentElement.lang,...navigator.languages])),Jt=_e(()=>f.includes(o(Xt).language)?"rtl":void 0),ke=_e(()=>({...o(Xt).strings})),S=_e(()=>o(ie)?.audio?.match(/^(https?:)?\//)?wn(o(ie).audio,o(V),{language:o(b).audioChallengeLanguage||o(Xt).language}).toString():o(ie)?.audio),G=_e(()=>o(ie)?.image?.match(/^(https?:)?\//)?wn(o(ie).image,o(V)):o(ie)?.image);xe(()=>{Qt({auto:t.auto,challenge:t.challenge,display:t.display,language:t.language,name:t.name,type:t.type,workers:t.workers})}),xe(()=>{t.theme?x?.setAttribute("theme",t.theme):x?.removeAttribute("theme")}),xe(()=>{if(t.configuration)try{Qt(JSON.parse(t.configuration))}catch{K("unable to parse the `configuration` attribute (JSON expected)")}}),xe(()=>{o(Ye)!==o(b).display&&kn(o(b).display)}),xe(()=>{o(ne)&&o(k)===H.VERIFYING&&_(ne,!1)}),xe(()=>{!o(ne)&&o(k)===H.VERIFIED&&_(ne,!0)}),xe(()=>{if(!o(ne)){let u=Xn();u&&u.checked&&(u.checked=!1)}}),xe(()=>{o(k)===H.VERIFIED&&Xn()?.setCustomValidity("")}),xe(()=>{if(o(Ge)==="onload"){let u=setTimeout(()=>{It()},10);return()=>{u&&clearTimeout(u)}}}),xe(()=>{o(ge)&&K("error:",o(ge))}),xe(()=>{o(W)&&o(b).setCookie&&vo(o(W),o(b).setCookie)}),qr(()=>(K("mounted","3.5.0"),x&&globalThis.$altcha.instances.add(x),_(F,eo(),!0),o(F)?.addEventListener("reset",ra),o(F)?.addEventListener("submit",aa,{capture:!0}),document.addEventListener("focusin",na),_t(),o(b).humanInteractionSignature&&(K("human interaction signature enabled"),B=new Rr),P("load"),c||K("secure context (HTTPS) required"),()=>{Gn(),x&&globalThis.$altcha.instances.delete(x),o(Ie)&&clearTimeout(o(Ie)),o(F)?.removeEventListener("reset",ra),o(F)?.removeEventListener("submit",aa,{capture:!0}),document.removeEventListener("focusin",na),B?.destroy()}));function _t(){_(it,[...globalThis.$altcha.plugins].map(u=>new u(x)),!0),K("activating plugins",o(it).map(u=>u.constructor.name));for(let u of o(it))u.activate()}async function Zt(u,...v){let y;for(let w of o(it))y=await w[u].call(w,...v);return y}function Gn(){for(let u of o(it))u.destroy()}function qn(u){let[v,y]=u.salt.split("?"),w={};if(y)try{Object.assign(w,Object.fromEntries(new URLSearchParams(y).entries()))}catch{}let A={codeChallenge:u.codeChallenge,parameters:{algorithm:u.algorithm,cost:1,data:w,expiresAt:w?.expires?parseInt(w.expires,10):void 0,keyLength:u.algorithm==="SHA-512"?64:u.algorithm==="SHA-384"?48:32,nonce:jl(new TextEncoder().encode(u.salt)),keyPrefix:u.challenge,salt:""},signature:u.signature};return Object.defineProperties(A,{_originalSalt:{enumerable:!1,value:u.salt,writable:!1},_version:{enumerable:!1,value:1,writable:!1}}),A}function Wn(u,v){return{algorithm:u.parameters.algorithm,challenge:u.parameters.keyPrefix,number:v.counter,salt:"_originalSalt"in u?u._originalSalt:u.parameters.nonce,signature:u.signature,took:v.time||0}}async function ye(u){await new Promise(v=>setTimeout(v,u))}async function qe(u=o(b).challenge,v){let y=await Zt("onFetchChallenge",u),w=null;if(y!==void 0)return y;if(typeof u=="string")if(u.startsWith("{")){K("parsing JSON challenge");try{w=JSON.parse(u)}catch{throw new Error("Unable to parse JSON challenge.")}}else{K("fetching challenge from",v?.method||"GET",u),_(V,new URL(u,location.origin),!0);let A=await o(b).fetch(u,{credentials:o(b).credentials||void 0,...v});await oa(A);let C=A.headers.get("x-altcha-config");C&&uo(C);let j=await A.json();if(j&&"his"in j&&j.his){if(K("requested HIS"),!B)throw new Error("Server requested HIS data but collector is disabled.");return qe(wn(j.his.url,o(V)),{body:JSON.stringify({his:B.export()}),headers:{"content-type":"application/json"},method:"POST"})}j&&"hisResult"in j&&j.hisResult&&K("HIS result",j.hisResult),w=j}else if(u&&typeof u=="object")try{w=JSON.parse(JSON.stringify(u))}catch{throw new Error("Unable to parse JSON challenge.")}if(Wi(w)&&(w=qn(w)),!Xi(w))throw new Error("Challenge validation failed.");return w}function Wi(u){return u!==null&&typeof u=="object"&&"challenge"in u}function Xi(u){return!!u&&typeof u=="object"&&"parameters"in u&&!!u.parameters&&typeof u.parameters=="object"&&"algorithm"in u.parameters&&"nonce"in u.parameters&&"salt"in u.parameters&&"keyPrefix"in u.parameters}function Xn(){return document.getElementById(o(Gt))}function Ji(u){switch(u){case"checkbox":return Vi;case"switch":return ji;default:return Fi}}function Zi(u,v){let y=Object.keys(u).map(A=>A.toLowerCase()),w=v.reduce((A,C)=>(C=C.toLowerCase(),A||(u[C]?C:null)||y.find(j=>C.split("-")[0]===j.split("-")[0])||null),null);return u[w||""]||(w="en"),{language:w,strings:u[w]}}function Qi(u){switch(u){case"bar":return o(b).barPlacement||"bottom";case"floating":return o(b).floatingPlacement||"auto";default:return}}function eo(){if(t.form){let v=x?.getRootNode()?.getElementById?.(t.form);return v instanceof HTMLFormElement?v:null}return o(D)?.closest("form")??null}function ta(u){return o(F)?Array.from(o(F).elements).filter(v=>v.matches(u)):[]}function to(u){return ta(l).reduce((y,w)=>{let A=w.name,C=w.value;return A&&C&&(y[A]=/\n/.test(C)?C.replace(new RegExp("(?<!\\r)\\n","g"),`\r
`):C),y},{})}function no(){try{return Intl.DateTimeFormat().resolvedOptions().timeZone}catch{}}function wn(u,v,y){let w=new URL(u,v);if(w.search||(w.search=v.search),y)for(let A in y)y[A]!==void 0&&y[A]!==null&&w.searchParams.set(A,y[A]);return w.toString()}function ro(u){!o(ne)&&u.currentTarget.checked?(u.preventDefault(),u.currentTarget.checked=!1,o(k)!==H.VERIFYING&&It()):u.currentTarget.checked||(u.preventDefault(),Pe())}function ao(u){o(k)===H.VERIFYING?u.currentTarget.setCustomValidity(o(ke).waitAlert):o(b).validationMessage&&u.currentTarget.setCustomValidity(o(b).validationMessage)}function io(){kn(o(b).display),Pe()}function oo(){xn()}function so(u){let v=u.target;o(b).display==="floating"&&v&&!x?.contains(v)&&!v.hasAttribute("data-backdrop")&&!v.closest("[data-popover]")&&o(k)!==H.VERIFIED&&!o(b).floatingPersist&&Jn()}function na(u){let v=u.target;o(Ge)==="onfocus"&&o(k)===H.UNVERIFIED&&o(F)&&v instanceof Element&&(o(F).contains(v)||v.form===o(F))&&It()}function ra(){kn(o(b).display),Pe()}function aa(u){u.target?.getAttribute("data-code-challenge")!=="true"&&o(Ge)==="onsubmit"&&o(k)===H.UNVERIFIED&&(u.preventDefault(),u.stopPropagation(),_(Ce,u.submitter,!0),Zn(),It().then(y=>{y&&!o(ie)&&xt().then(()=>{ia(o(Ce))})}))}function lo(u){u.persisted&&(kn(o(b).display),Pe())}function co(){xn()}function uo(u){try{let v=JSON.parse(u);v&&typeof v=="object"&&Qt({serverVerificationFields:v?.sentinel?.fields,serverVerificationTimeZone:v?.sentinel?.timeZone,verifyUrl:v.verifyurl,...v})}catch(v){K("unable to configure from x-altcha-config header",v)}}function fo(u=20){if(!o(D))return;let v=o(b).floatingPlacement;if(!o(Z)&&(_(Z,(o(b).floatingAnchor instanceof HTMLElement?o(b).floatingAnchor:o(b).floatingAnchor?document.querySelector(o(b).floatingAnchor):ta(s)[0])||o(F),!0),!o(Z))){K("unable to find floating anchor element");return}let y=parseInt(o(b).floatingOffset,10)||12,w=o(Z).getBoundingClientRect(),A=o(D).getBoundingClientRect(),C=document.documentElement.clientHeight,j=document.documentElement.clientWidth,Ae=!v||v==="auto"?w.bottom+A.height+y+u>C:v==="top",q=Math.max(u,Math.min(j-u-A.width,w.left+w.width/2-A.width/2));if(o(D).style.setProperty("--altcha-floating-left",`${q}px`),o(D).style.setProperty("--altcha-floating-top",Ae?`${w.top-(A.height+y)}px`:`${w.bottom+y}px`),o(D).setAttribute("data-floating-position",Ae?"top":"bottom"),o(L)){let fe=o(L).getBoundingClientRect();o(L).style.left=w.left-q+w.width/2-fe.width/2+"px"}}async function ho(u,v){let y=await Zt("onRequestServerVerification",u,v);if(y!==void 0)return y;if(K("requesting server verification from",o(b).verifyUrl),!o(b).verifyUrl)throw new Error("Parameter verifyUrl must be set for server verification.");let w=await o(b).fetch(wn(o(b).verifyUrl,o(V)),{body:JSON.stringify({code:v,fields:o(b).serverVerificationFields?to():void 0,payload:u,timeZone:o(b).serverVerificationTimeZone?no():void 0}),credentials:o(b).credentials||void 0,headers:{"Content-Type":"application/json"},method:"POST"});await oa(w);let A=await w.json();return A&&typeof A=="object"&&"payload"in A&&A.payload&&P("serververification",A),A}function ia(u){o(F)&&"requestSubmit"in o(F)?o(F).requestSubmit(u):o(F)?.reportValidity()&&(u?u.click():o(F).submit())}function vo(u,v={}){let{domain:y,name:w=o(b).name,maxAge:A,path:C,sameSite:j,secure:Ae}=v,q=`${encodeURIComponent(w)}=${encodeURIComponent(u)}`;y&&(q+=`; Domain=${y}`),A!=null&&(q+=`; Max-Age=${A}`),C&&(q+=`; Path=${C}`),j&&(q+=`; SameSite=${j}`),Ae&&(q+="; Secure"),document.cookie=q}function kn(u){switch(u){case"bar":case"floating":case"overlay":Jn(),(!o(Ge)||o(Ge)==="off")&&(o(ue).auto="onsubmit");break;case"standard":Zn()}o(Ye)!==u&&_(Ye,u,!0)}function po(u){o(Ie)&&clearTimeout(o(Ie));let v=()=>{o(k)!==H.UNVERIFIED?(_(ne,!1),Oe(H.EXPIRED)):Pe(),P("expired")},y=u*1e3-Date.now();y>=1?_(Ie,setTimeout(v,y),!0):v()}async function oa(u){if(u.status>=400){if(u.headers.get("content-type")?.includes("/json")){let y;try{y=await u.json()}catch{}if(y&&"error"in y)throw new Error(`Server responded with ${u.status} - ${y.error}`)}throw new Error(`Server responded with ${u.status}.`)}let v=u.headers.get("content-type");if(!v||!v.includes("/json"))throw new Error(`Server responded with invalid content-type. Expected application/json, received ${v}.`)}async function sa(u){if(!o(W)){Oe(H.ERROR,"Cannot verify code challenge without PoW payload.");return}Oe(H.VERIFYING);let v=null;if(o(b).verifyUrl)v=await ho(o(W),u);else if(o(b).verifyFunction)v=await o(b).verifyFunction(o(W),u);else{Oe(H.ERROR,"Parameter verifyUrl is required for code challenge verification.");return}v?.payload&&(_(W,v.payload,!0),K("server payload",o(W))),v?.verified===!0?(K("verified"),Oe(H.VERIFIED),P("verified",{payload:o(W)}),o(Ge)==="onsubmit"&&xt().then(()=>{ia(o(Ce))})):Oe(H.ERROR,v?.reason||"Verification failed."),o(b).disableAutoFocus||Xn()?.focus()}function Qt(u){Object.assign(o(ue),{...Object.fromEntries(Object.entries(u).filter(([v,y])=>y!==void 0))})}function go(){return{...o(b)}}function mo(){return o(k)}function Jn(){_(Q,!1)}function K(...u){(o(b).debug||u.some(v=>v instanceof Error))&&console[u[0]instanceof Error?"error":"log"]("ALTCHA",`[name=${o(b).name}]`,...u)}function Pe(u=H.UNVERIFIED,v=null){_(ne,!1),_(ge,v,!0),_(W,null),o(z)&&o(z).abort(),o(Ie)&&(clearTimeout(o(Ie)),_(Ie,null)),Oe(u)}function Oe(u,v=null){_(k,u,!0),_(ge,v,!0),P("statechange",{payload:o(W),state:o(k)})}function Zn(){_(Q,!0),xt().then(()=>{xn()})}function xn(){if(o(b).display==="floating")return fo();_(E,o(E)+1)}async function It(u={}){let{concurrency:v=Math.max(1,o(b).workers),controller:y=new AbortController,minDuration:w=o(b).minDuration}=u,A=performance.now(),C=null,j=null,Ae=!1,q=await Zt("onVerify",u);if(q!==void 0)return q;Pe(H.VERIFYING),_(z,y,!0);try{if(!c)throw new Error("Secure context (HTTPS) required.");if(o(b).mockError)throw new Error("Mock error.");if(o(b).test)return K("running test mode with null challenge"),await ye(Math.max(0,w-(performance.now()-A))),o(z)?.signal.aborted?(Pe(),null):(_(W,btoa(JSON.stringify({challenge:null,solution:null,test:!0})),!0),K("verified"),Oe(H.VERIFIED),P("verified",{payload:o(W)}),{payload:o(W)});if(C=await qe(),!C)throw new Error("Failed to fetch challenge.");K("challenge",C),"configuration"in C&&(K("re-configuring from challenge",C.configuration),Qt(C.configuration)),C.parameters.expiresAt&&po(C.parameters.expiresAt),Ae="_version"in C&&C._version===1;let fe=globalThis.$altcha.algorithms.get(C.parameters.algorithm);if(!fe)throw new Error(`Unsupported algorithm ${C.parameters.algorithm}.`);if(j=await Ki({challenge:C,concurrency:v,controller:y,createWorker:fe,counterMode:Ae?"string":"uint32",onOutOfMemory:ft=>{if(K("out of memory error received"),P("outofmemory"),o(b).retryOnOutOfMemoryError&&ft>1){let ht=Math.floor(ft/2);return K(`retrying with ${ht} workers...`),ht}},timeout:o(b).timeout}),o(z)?.signal.aborted)return Pe(),null;if(!j)throw new Error("Failed to find solution.");K("solution",j),await ye(Math.max(0,w-(performance.now()-A))),_(ie,C.codeChallenge||o(b).codeChallenge||null,!0),Ae?_(W,btoa(JSON.stringify(Wn(C,j))),!0):_(W,btoa(JSON.stringify({challenge:{parameters:C.parameters,signature:C.signature},solution:j})),!0),o(ie)?(K("requesting code verification"),Oe(H.CODE),P("codechallenge",{codeChallenge:o(ie)})):o(b).verifyUrl?await sa():(K("verified"),Oe(H.VERIFIED),P("verified",{payload:o(W)}))}catch(fe){return K("verification failed",fe),Oe(H.ERROR,String(fe)),null}finally{_(z,null)}return{challenge:C,payload:o(W),solution:j}}var bo={configure:Qt,getConfiguration:go,getState:mo,hide:Jn,log:K,reset:Pe,setState:Oe,show:Zn,updateUI:xn,verify:It},la=Jl();se("scroll",gr,oo),se("click",gr,so),se("pageshow",Et,lo),se("resize",Et,co);var ca=Lt(la);{var yo=u=>{var v=Bl();N(u,v)};oe(ca,u=>{o(b).display==="overlay"&&o(Q)&&u(yo)})}var We=X(ca,2),ua=he(We);{var _o=u=>{var v=Yl(),y=Lt(v),w=X(y,2);{var A=C=>{var j=Kl();Ii(j,()=>document.querySelector(o(b).overlayContent)?.innerHTML,!0),ae(j),N(C,j)};oe(w,C=>{o(b).overlayContent&&C(A)})}se("click",y,io,!0),N(u,v)};oe(ua,u=>{o(b).display==="overlay"&&o(Q)&&u(_o)})}var Qn=X(ua,2),er=he(Qn),tr=he(er),fa=he(tr);{let u=_e(()=>o(b).display==="standard"&&o(Ge)!=="onsubmit"||o(k)===H.VERIFYING);nl(fa,()=>o(qt),(v,y)=>{y(v,{get id(){return o(Gt)},get form(){return t.form},name:"",get required(){return o(u)},get loading(){return o(Wt)},get checked(){return o(ne)},onchange:ro,oninvalid:ao})})}var nr=X(fa,2),wo=he(nr);{var ko=u=>{var v=En();me(()=>Xe(v,o(ke).verificationRequired)),N(u,v)},xo=u=>{var v=En();me(()=>Xe(v,o(ke).verifying)),N(u,v)},Eo=u=>{var v=En();me(()=>Xe(v,o(ke).verified)),N(u,v)},So=u=>{var v=En();me(()=>Xe(v,o(ke).label)),N(u,v)};oe(wo,u=>{o(k)===H.CODE&&o(ie)?u(ko):o(k)===H.VERIFYING?u(xo,1):o(k)===H.VERIFIED?u(Eo,2):u(So,-1)})}ae(nr),ae(tr);var Co=X(tr,2);{var Ao=u=>{Jr(u,{get strings(){return o(ke)}})};oe(Co,u=>{o(yt)&&u(Ao)})}ae(er);var ha=X(er,2);{var To=u=>{{let v=_e(()=>o(b).display==="bar"&&o(yt));Tr(u,{get logo(){return o(v)},get strings(){return o(ke)}})}};oe(ha,u=>{o(_n)&&u(To)})}var da=X(ha,2);{var $o=u=>{var v=Gl();mt(v,y=>_(L,y),()=>o(L)),N(u,v)};oe(da,u=>{o(b).display==="floating"&&u($o)})}var Ro=X(da,2);{var Io=u=>{var v=ql();Wr(v),me(()=>{U(v,"form",t.form),U(v,"name",o(b).name),pl(v,o(W))}),N(u,v)};oe(Ro,u=>{o(b).setCookie||u(Io)})}ae(Qn);var Po=X(Qn,2);{var Oo=u=>{$r(u,{get anchor(){return o(D)},onClickOutside:()=>{c&&Pe()},get placement(){return o(b).popoverPlacement},role:"alert",variant:"error",get dir(){return o(Jt)},get updateUISignal(){return o(E)},children:(v,y)=>{var w=Ca(),A=Lt(w);{var C=q=>{var fe=Wl();N(q,fe)},j=q=>{var fe=Pa(),ft=Nt(fe,!0);me(()=>Xe(ft,o(ke).expired)),N(q,fe)},Ae=q=>{var fe=Pa(),ft=Nt(fe,!0);me(()=>{U(fe,"title",o(ge)),Xe(ft,o(ke).error)}),N(q,fe)};oe(A,q=>{!o(ge)&&!c?q(C):!o(ge)&&o(k)===H.EXPIRED?q(j,1):q(Ae,-1)})}N(v,w)},$$slots:{default:!0}})},Lo=u=>{var v=Ca(),y=Lt(v);tl(y,()=>o(ie),w=>{{let A=_e(()=>o(b).codeChallengeDisplay!=="standard");$r(w,{get anchor(){return o(D)},get backdrop(){return o(A)},get display(){return o(b).codeChallengeDisplay},onClose:()=>{Pe()},get placement(){return o(b).popoverPlacement},role:"dialog",get"aria-label"(){return o(ke).verificationRequired},get dir(){return o(Jt)},get updateUISignal(){return o(E)},children:(C,j)=>{var Ae=Xl(),q=Lt(Ae);Bi(q,{get audioUrl(){return o(S)},get imageUrl(){return o(G)},onCancel:()=>Pe(),onReload:()=>It(),onSubmit:ht=>sa(ht),get codeChallenge(){return o(ie)},get config(){return o(b)},get strings(){return o(ke)}});var fe=X(q,2);{var ft=ht=>{Tr(ht,{get logo(){return o(yt)},get strings(){return o(ke)}})};oe(fe,ht=>{o(_n)&&o(b).codeChallengeDisplay!=="standard"&&ht(ft)})}N(C,Ae)},$$slots:{default:!0}})}}),N(u,v)};oe(Po,u=>{o(ge)||o(k)===H.EXPIRED||!c?u(Oo):o(ie)&&o(k)===H.CODE&&u(Lo,1)})}ae(We),mt(We,u=>_(D,u),()=>o(D)),me(u=>{U(We,"data-state",o(k)),U(We,"data-display",o(b).display||void 0),U(We,"data-placement",u),U(We,"data-visible",o(Q)||void 0),U(We,"dir",o(Jt)),U(nr,"for",o(Gt)),We.dir=We.dir},[()=>Qi(o(b).display)]),N(e,la);var No=ut(bo);return i(),No}typeof window<"u"&&window.customElements&&!customElements.get("altcha-widget")&&customElements.define("altcha-widget",bt(Zl,{auto:{type:"String"},challenge:{type:"String"},configuration:{type:"String"},display:{type:"String"},form:{type:"String"},language:{type:"String"},name:{type:"String"},theme:{type:"String"},type:{type:"String"},workers:{type:"Number"}},[],["configure","getConfiguration","getState","hide","log","reset","setState","show","updateUI","verify"]));var Yi=`(function() {
  "use strict";
  function assertAlgorithm(algorithm, allowed) {
    if (!allowed.includes(algorithm)) {
      throw new Error(
        \`Unsupported algorithm: \${String(algorithm)}. Expected one of: \${allowed.join(", ")}.\`
      );
    }
  }
  function bufferStartsWith(buffer, prefix) {
    if (prefix.length > buffer.length) {
      return false;
    }
    for (let i = 0; i < prefix.length; i++) {
      if (buffer[i] !== prefix[i]) {
        return false;
      }
    }
    return true;
  }
  function bufferToHex(buffer) {
    return Array.from(new Uint8Array(buffer)).map((b) => b.toString(16).padStart(2, "0")).join("");
  }
  function concatBuffers(a, b) {
    const out = new Uint8Array(a.length + b.length);
    out.set(a, 0);
    out.set(b, a.length);
    return out;
  }
  function hexToBuffer(hex) {
    if (hex.length % 2 !== 0) {
      throw new Error(\`Hex string must have an even length. Got: \${hex}\`);
    }
    if (!/^[0-9a-fA-F]*$/.test(hex)) {
      throw new Error("Hex string contains non-hex characters.");
    }
    const buffer = new Uint8Array(hex.length / 2);
    for (let i = 0; i < buffer.length; i++) {
      buffer[i] = parseInt(hex.substring(i * 2, i * 2 + 2), 16);
    }
    return buffer;
  }
  async function delay(ms) {
    await new Promise((resolve) => setTimeout(resolve, ms));
  }
  function timeDuration(start) {
    return Math.floor((performance.now() - start) * 10) / 10;
  }
  var HmacAlgorithm = /* @__PURE__ */ ((HmacAlgorithm2) => {
    HmacAlgorithm2["SHA_256"] = "SHA-256";
    HmacAlgorithm2["SHA_384"] = "SHA-384";
    HmacAlgorithm2["SHA_512"] = "SHA-512";
    return HmacAlgorithm2;
  })(HmacAlgorithm || {});
  Object.values(HmacAlgorithm);
  const MAX_COUNTER = {
    string: Number.MAX_SAFE_INTEGER,
    uint32: 4294967295
  };
  function isValidCounter(n, mode) {
    return Number.isInteger(n) && n >= 0 && n <= MAX_COUNTER[mode];
  }
  class PasswordBuffer {
    constructor(nonce, mode = "uint32") {
      this.nonce = nonce;
      this.mode = mode;
      this.buffer = new Uint8Array(this.nonce.length + this.COUNTER_BYTES);
      this.buffer.set(this.nonce, 0);
      this.dataView = new DataView(this.buffer.buffer);
    }
    nonce;
    mode;
    COUNTER_BYTES = 4;
    buffer;
    dataView;
    encoder = new TextEncoder();
    /**
     * Appends the counter to the nonce buffer.
     * In 'string' mode, encodes the counter as a UTF-8 string.
     * In 'uint32' mode, writes the counter as a big-endian 32-bit integer.
     * Throws a RangeError unless the counter is an integer the mode encodes exactly.
     */
    setCounter(n) {
      if (!isValidCounter(n, this.mode)) {
        throw new RangeError(
          \`counter must be an integer from 0 to \${MAX_COUNTER[this.mode]}. Got: \${n}\`
        );
      }
      if (this.mode === "string") {
        return concatBuffers(this.nonce, this.encoder.encode(n.toString()));
      }
      this.dataView.setUint32(this.nonce.length, n, false);
      return this.buffer;
    }
  }
  function assertKeyPrefix(keyPrefix, keyLength) {
    if (typeof keyPrefix !== "string" || !/^[0-9a-fA-F]+$/.test(keyPrefix)) {
      throw new Error("keyPrefix must be a non-empty hex string.");
    }
    if (keyPrefix.length > keyLength * 2) {
      throw new Error(
        \`keyPrefix (\${keyPrefix.length} hex characters) must not be longer than the key (keyLength: \${keyLength} bytes).\`
      );
    }
  }
  async function solveChallenge(options) {
    const {
      challenge,
      controller,
      counterMode = "uint32",
      counterStart = 0,
      counterStep = 1,
      deriveKey: deriveKey2,
      timeout = 9e4
    } = options;
    const { nonce, keyLength = 32, keyPrefix, salt } = challenge.parameters;
    assertKeyPrefix(keyPrefix, keyLength);
    const nonceBuf = hexToBuffer(nonce);
    const saltBuf = hexToBuffer(salt);
    const keyPrefixHex = keyPrefix.toLowerCase();
    const keyPrefixBuf = keyPrefix.length % 2 === 0 ? hexToBuffer(keyPrefix) : null;
    const password = new PasswordBuffer(nonceBuf, counterMode);
    const start = performance.now();
    let counter = counterStart;
    let iterations = 0;
    let derivedKeyHex = "";
    let lastYield = start;
    while (true) {
      if (controller?.signal.aborted || timeout && iterations % 10 === 0 && performance.now() - start > timeout) {
        return null;
      }
      const { derivedKey } = await deriveKey2(
        challenge.parameters,
        saltBuf,
        password.setCounter(counter)
      );
      if (iterations % 10 === 0 && performance.now() - lastYield > 200) {
        await delay(0);
        lastYield = performance.now();
      }
      if (keyPrefixBuf ? bufferStartsWith(derivedKey, keyPrefixBuf) : bufferToHex(derivedKey).startsWith(keyPrefixHex)) {
        derivedKeyHex = bufferToHex(derivedKey);
        break;
      }
      counter = counter + counterStep;
      iterations = iterations + 1;
    }
    return {
      counter,
      derivedKey: derivedKeyHex,
      time: timeDuration(start)
    };
  }
  function handler(options) {
    const { deriveKey: deriveKey2 } = options;
    let controller = void 0;
    self.onmessage = async (message) => {
      const { challenge, counterMode, counterStart, counterStep, timeout, type } = message.data;
      if (type === "abort") {
        controller?.abort();
      } else if (type === "work") {
        controller = new AbortController();
        let solution;
        try {
          solution = await solveChallenge({
            challenge,
            controller,
            counterStart,
            counterStep,
            deriveKey: deriveKey2,
            counterMode,
            timeout
          });
        } catch (err) {
          return self.postMessage({ error: err });
        }
        self.postMessage(solution);
      }
    };
  }
  async function deriveKey(parameters, salt, password) {
    const { algorithm, cost, keyLength = 32 } = parameters;
    assertAlgorithm(algorithm, ["PBKDF2/SHA-256", "PBKDF2/SHA-384", "PBKDF2/SHA-512"]);
    const passwordKey = await crypto.subtle.importKey(
      "raw",
      password,
      { name: "PBKDF2" },
      false,
      ["deriveBits"]
    );
    const derivedBits = await crypto.subtle.deriveBits(
      {
        name: "PBKDF2",
        salt,
        iterations: cost,
        hash: algorithm.slice("PBKDF2/".length)
      },
      passwordKey,
      keyLength * 8
    );
    return {
      parameters: {},
      derivedKey: new Uint8Array(derivedBits)
    };
  }
  handler({
    deriveKey
  });
})();
`,Oa=typeof self<"u"&&self.Blob&&new Blob(["(self.URL || self.webkitURL).revokeObjectURL(self.location.href);",Yi],{type:"text/javascript;charset=utf-8"});function Zr(e){let t;try{if(t=Oa&&(self.URL||self.webkitURL).createObjectURL(Oa),!t)throw"";let n=new Worker(t,{name:e?.name});return n.addEventListener("error",()=>{(self.URL||self.webkitURL).revokeObjectURL(t)}),n}catch{return new Worker("data:text/javascript;charset=utf-8,"+encodeURIComponent(Yi),{name:e?.name})}}var Gi=`(function() {
  "use strict";
  function assertAlgorithm(algorithm, allowed) {
    if (!allowed.includes(algorithm)) {
      throw new Error(
        \`Unsupported algorithm: \${String(algorithm)}. Expected one of: \${allowed.join(", ")}.\`
      );
    }
  }
  function bufferStartsWith(buffer, prefix) {
    if (prefix.length > buffer.length) {
      return false;
    }
    for (let i = 0; i < prefix.length; i++) {
      if (buffer[i] !== prefix[i]) {
        return false;
      }
    }
    return true;
  }
  function bufferToHex(buffer) {
    return Array.from(new Uint8Array(buffer)).map((b) => b.toString(16).padStart(2, "0")).join("");
  }
  function concatBuffers(a, b) {
    const out = new Uint8Array(a.length + b.length);
    out.set(a, 0);
    out.set(b, a.length);
    return out;
  }
  function hexToBuffer(hex) {
    if (hex.length % 2 !== 0) {
      throw new Error(\`Hex string must have an even length. Got: \${hex}\`);
    }
    if (!/^[0-9a-fA-F]*$/.test(hex)) {
      throw new Error("Hex string contains non-hex characters.");
    }
    const buffer = new Uint8Array(hex.length / 2);
    for (let i = 0; i < buffer.length; i++) {
      buffer[i] = parseInt(hex.substring(i * 2, i * 2 + 2), 16);
    }
    return buffer;
  }
  async function delay(ms) {
    await new Promise((resolve) => setTimeout(resolve, ms));
  }
  function timeDuration(start) {
    return Math.floor((performance.now() - start) * 10) / 10;
  }
  var HmacAlgorithm = /* @__PURE__ */ ((HmacAlgorithm2) => {
    HmacAlgorithm2["SHA_256"] = "SHA-256";
    HmacAlgorithm2["SHA_384"] = "SHA-384";
    HmacAlgorithm2["SHA_512"] = "SHA-512";
    return HmacAlgorithm2;
  })(HmacAlgorithm || {});
  Object.values(HmacAlgorithm);
  const MAX_COUNTER = {
    string: Number.MAX_SAFE_INTEGER,
    uint32: 4294967295
  };
  function isValidCounter(n, mode) {
    return Number.isInteger(n) && n >= 0 && n <= MAX_COUNTER[mode];
  }
  class PasswordBuffer {
    constructor(nonce, mode = "uint32") {
      this.nonce = nonce;
      this.mode = mode;
      this.buffer = new Uint8Array(this.nonce.length + this.COUNTER_BYTES);
      this.buffer.set(this.nonce, 0);
      this.dataView = new DataView(this.buffer.buffer);
    }
    nonce;
    mode;
    COUNTER_BYTES = 4;
    buffer;
    dataView;
    encoder = new TextEncoder();
    /**
     * Appends the counter to the nonce buffer.
     * In 'string' mode, encodes the counter as a UTF-8 string.
     * In 'uint32' mode, writes the counter as a big-endian 32-bit integer.
     * Throws a RangeError unless the counter is an integer the mode encodes exactly.
     */
    setCounter(n) {
      if (!isValidCounter(n, this.mode)) {
        throw new RangeError(
          \`counter must be an integer from 0 to \${MAX_COUNTER[this.mode]}. Got: \${n}\`
        );
      }
      if (this.mode === "string") {
        return concatBuffers(this.nonce, this.encoder.encode(n.toString()));
      }
      this.dataView.setUint32(this.nonce.length, n, false);
      return this.buffer;
    }
  }
  function assertKeyPrefix(keyPrefix, keyLength) {
    if (typeof keyPrefix !== "string" || !/^[0-9a-fA-F]+$/.test(keyPrefix)) {
      throw new Error("keyPrefix must be a non-empty hex string.");
    }
    if (keyPrefix.length > keyLength * 2) {
      throw new Error(
        \`keyPrefix (\${keyPrefix.length} hex characters) must not be longer than the key (keyLength: \${keyLength} bytes).\`
      );
    }
  }
  async function solveChallenge(options) {
    const {
      challenge,
      controller,
      counterMode = "uint32",
      counterStart = 0,
      counterStep = 1,
      deriveKey: deriveKey2,
      timeout = 9e4
    } = options;
    const { nonce, keyLength = 32, keyPrefix, salt } = challenge.parameters;
    assertKeyPrefix(keyPrefix, keyLength);
    const nonceBuf = hexToBuffer(nonce);
    const saltBuf = hexToBuffer(salt);
    const keyPrefixHex = keyPrefix.toLowerCase();
    const keyPrefixBuf = keyPrefix.length % 2 === 0 ? hexToBuffer(keyPrefix) : null;
    const password = new PasswordBuffer(nonceBuf, counterMode);
    const start = performance.now();
    let counter = counterStart;
    let iterations = 0;
    let derivedKeyHex = "";
    let lastYield = start;
    while (true) {
      if (controller?.signal.aborted || timeout && iterations % 10 === 0 && performance.now() - start > timeout) {
        return null;
      }
      const { derivedKey } = await deriveKey2(
        challenge.parameters,
        saltBuf,
        password.setCounter(counter)
      );
      if (iterations % 10 === 0 && performance.now() - lastYield > 200) {
        await delay(0);
        lastYield = performance.now();
      }
      if (keyPrefixBuf ? bufferStartsWith(derivedKey, keyPrefixBuf) : bufferToHex(derivedKey).startsWith(keyPrefixHex)) {
        derivedKeyHex = bufferToHex(derivedKey);
        break;
      }
      counter = counter + counterStep;
      iterations = iterations + 1;
    }
    return {
      counter,
      derivedKey: derivedKeyHex,
      time: timeDuration(start)
    };
  }
  function handler(options) {
    const { deriveKey: deriveKey2 } = options;
    let controller = void 0;
    self.onmessage = async (message) => {
      const { challenge, counterMode, counterStart, counterStep, timeout, type } = message.data;
      if (type === "abort") {
        controller?.abort();
      } else if (type === "work") {
        controller = new AbortController();
        let solution;
        try {
          solution = await solveChallenge({
            challenge,
            controller,
            counterStart,
            counterStep,
            deriveKey: deriveKey2,
            counterMode,
            timeout
          });
        } catch (err) {
          return self.postMessage({ error: err });
        }
        self.postMessage(solution);
      }
    };
  }
  async function deriveKey(parameters, salt, password) {
    const { algorithm, keyLength = 32 } = parameters;
    assertAlgorithm(algorithm, ["SHA-256", "SHA-384", "SHA-512"]);
    const iterations = Math.max(1, parameters.cost);
    let derivedKey = concatBuffers(salt, password);
    for (let i = 0; i < iterations; i++) {
      derivedKey = new Uint8Array(await crypto.subtle.digest(algorithm, derivedKey));
    }
    return {
      parameters: {},
      derivedKey: derivedKey.slice(0, keyLength)
    };
  }
  handler({
    deriveKey
  });
})();
`,La=typeof self<"u"&&self.Blob&&new Blob(["(self.URL || self.webkitURL).revokeObjectURL(self.location.href);",Gi],{type:"text/javascript;charset=utf-8"});function Qr(e){let t;try{if(t=La&&(self.URL||self.webkitURL).createObjectURL(La),!t)throw"";let n=new Worker(t,{name:e?.name});return n.addEventListener("error",()=>{(self.URL||self.webkitURL).revokeObjectURL(t)}),n}catch{return new Worker("data:text/javascript;charset=utf-8,"+encodeURIComponent(Gi),{name:e?.name})}}var Ql=`:root {
  --altcha-border-color: var(--altcha-color-neutral);
  --altcha-border-width: 1px;
  --altcha-border-radius: 6px;
  --altcha-color-base: light-dark(oklch(100% 0.00011 271.152), oklch(20.904% 0.00002 271.152));
  --altcha-color-base-content: light-dark(
  	oklch(20.904% 0.00002 271.152),
  	oklch(100% 0.00011 271.152)
  );
  --altcha-color-error: oklch(51.284% 0.20527 28.678);
  --altcha-color-error-content: oklch(100% 0.00011 271.152);
  --altcha-color-neutral: light-dark(oklch(83.591% 0.0001 271.152), oklch(46.04% 0.00005 271.152));
  --altcha-color-neutral-content: light-dark(
  	oklch(46.76% 0.00005 271.152),
  	oklch(100% 0.00011 271.152)
  );
  --altcha-color-primary: oklch(40.279% 0.2449 268.131);
  --altcha-color-primary-content: oklch(100% 0.00011 271.152);
  --altcha-color-success: oklch(55.748% 0.18968 142.511);
  --altcha-color-success-content: oklch(100% 0.00011 271.152);
  --altcha-checkbox-border-color: light-dark(
  	oklch(66.494% 0.00233 15.434),
  	oklch(51.028% 0.00006 271.152)
  );
  --altcha-checkbox-border-radius: 5px;
  --altcha-checkbox-border-width: var(--altcha-border-width);
  --altcha-checkbox-outline: 2px solid var(--altcha-checkbox-outline-color);
  --altcha-checkbox-outline-color: -webkit-focus-ring-color;
  --altcha-checkbox-outline-offset: 2px;
  --altcha-checkbox-size: 22px;
  --altcha-checkbox-transition-duration: var(--altcha-transition-duration);
  --altcha-input-background-color: var(--altcha-color-base);
  --altcha-input-border-radius: 3px;
  --altcha-input-border-width: 1px;
  --altcha-input-color: var(--altcha-color-base-content);
  --altcha-max-width: 320px;
  --altcha-padding: 0.75rem;
  --altcha-popover-arrow-size: 6px;
  --altcha-popover-color: var(--altcha-border-color);
  --altcha-shadow: drop-shadow(3px 3px 6px oklch(0% 0 0 / 0.2));
  --altcha-spinner-color: var(--altcha-color-base-content);
  --altcha-switch-background-color: var(--altcha-color-neutral);
  --altcha-switch-border-radius: calc(infinity * 1px);
  --altcha-switch-height: var(--altcha-checkbox-size);
  --altcha-switch-padding: 0.25rem;
  --altcha-switch-width: calc(var(--altcha-checkbox-size) * 1.75);
  --altcha-switch-toggle-border-radius: 100%;
  --altcha-switch-toggle-color: var(--altcha-color-neutral-content);
  --altcha-switch-toggle-size: calc(
  	var(--altcha-switch-height) - calc(var(--altcha-switch-padding) * 2)
  );
  --altcha-transition-duration: 0.6s;
  --altcha-z-index: 99999999;
  --altcha-z-index-popover: 999999999;
}

@supports (-moz-appearance: none) {
  :root {
    --altcha-checkbox-outline-color: var(--altcha-color-primary);
  }
}
.altcha {
  all: revert-layer;
  display: none;
  font-family: inherit;
  font-size: inherit;
  position: relative;
}
.altcha[data-visible] {
  display: block;
}
.altcha-popover, .altcha-popover * {
  all: revert-layer;
  box-sizing: border-box;
  font-family: inherit;
  font-size: inherit;
  line-height: 1.25;
}
.altcha * {
  all: revert-layer;
  box-sizing: border-box;
  font-family: inherit;
  font-size: inherit;
  line-height: 1.25;
}
.altcha a, .altcha-popover a {
  color: currentColor;
  text-decoration: none;
}
.altcha a:hover, .altcha-popover a:hover {
  color: currentColor;
}
.altcha-main {
  align-items: start;
  background-color: var(--altcha-color-base);
  border: var(--altcha-border-width, 1px) solid var(--altcha-border-color);
  border-radius: var(--altcha-border-radius, 0);
  color: var(--altcha-color-base-content);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  justify-content: space-between;
  padding: var(--altcha-padding);
  max-width: var(--altcha-max-width, 100%);
}
.altcha-main > * {
  display: flex;
  width: 100%;
}
.altcha-main > *:first-child {
  flex-grow: 1;
}
.altcha-checkbox-wrap {
  align-items: center;
  display: flex;
  flex-direction: row;
  flex-grow: 1;
  gap: 0.5rem;
}
.altcha-checkbox-wrap > * {
  display: flex;
}
.altcha-logo {
  opacity: 0.7;
}
.altcha-footer {
  align-items: center;
  display: flex;
  flex-grow: 1;
  gap: 0.5rem;
  justify-content: flex-end;
  font-size: 0.7rem;
  opacity: 0.7;
}
.altcha-footer p {
  margin: 0;
  padding: 0;
}
.altcha-error {
  font-size: 0.85rem;
}
.altcha-button {
  align-items: center;
  background: var(--altcha-color-primary);
  border: var(--altcha-input-border-width) solid var(--altcha-color-primary);
  border-radius: var(--altcha-input-border-radius);
  color: var(--altcha-color-primary-content);
  cursor: pointer;
  display: flex;
  font-size: 0.9rem;
  gap: 0.5rem;
  padding: 0.35rem;
}
.altcha-button:focus {
  border-color: var(--altcha-color-primary);
  outline: var(--altcha-checkbox-outline);
  outline-offset: var(--altcha-checkbox-outline-offset);
}
.altcha-button > .altcha-spinner, .altcha-button > svg {
  height: 20px;
  width: 20px;
}
.altcha-button-secondary {
  background: transparent;
  border-color: var(--altcha-color-neutral);
  color: var(--altcha-color-neutral-content);
}
.altcha-input {
  background: var(--altcha-input-background-color);
  border: var(--altcha-input-border-width) solid var(--altcha-color-neutral);
  border-radius: var(--altcha-input-border-radius);
  color: var(--altcha-input-color);
  flex-grow: 1;
  font-size: 1rem;
  min-width: 0;
  padding: 0.25rem;
  width: auto;
}
.altcha-input:focus {
  border-color: var(--altcha-color-primary);
  outline: var(--altcha-checkbox-outline);
  outline-offset: var(--altcha-checkbox-outline-offset);
}
.altcha-spinner {
  animation: altcha-rotate 0.6s linear infinite;
  border-radius: 100%;
  border: var(--altcha-checkbox-border-width) solid var(--altcha-spinner-color);
  border-bottom-color: transparent;
  border-right-color: transparent;
  opacity: 0.7;
}
.altcha-popover {
  background-color: var(--altcha-color-base);
  border: var(--altcha-border-width) solid var(--altcha-border-color);
  border-radius: var(--altcha-border-radius);
  color: var(--altcha-color-base-content);
  filter: var(--altcha-shadow);
  position: absolute;
  left: calc(var(--altcha-padding) / 2);
  max-width: calc(var(--altcha-max-width) - var(--altcha-padding));
  top: calc(var(--altcha-padding) + var(--altcha-checkbox-size) + var(--altcha-popover-arrow-size));
  z-index: var(--altcha-z-index-popover);
}
.altcha-popover-arrow {
  border: var(--altcha-popover-arrow-size) solid transparent;
  border-bottom-color: var(--altcha-popover-color);
  content: "";
  height: 0;
  left: calc(var(--altcha-checkbox-size) / 2);
  position: absolute;
  top: calc(var(--altcha-popover-arrow-size) * -2);
  width: 0;
}
.altcha-popover-content {
  max-height: 100dvh;
  overflow: auto;
  padding: var(--altcha-padding);
}
.altcha-popover[data-top=true][data-display=standard] {
  bottom: calc(100% - (var(--altcha-padding) - var(--altcha-popover-arrow-size)));
  top: auto;
}
.altcha-popover[data-top=true][data-display=standard] .altcha-popover-arrow {
  border-bottom-color: transparent;
  border-top-color: var(--altcha-popover-color);
  bottom: calc(var(--altcha-popover-arrow-size) * -2);
  top: auto;
}
.altcha-popover[data-variant=error] {
  --altcha-popover-color: var(--altcha-color-error);
  background-color: var(--altcha-color-error);
  border-color: var(--altcha-color-error);
  color: var(--altcha-color-error-content);
}
.altcha-popover[data-variant=error] .altcha-popover-content {
  padding: calc(var(--altcha-padding) / 1.5) var(--altcha-padding);
}
.altcha-popover[data-display=overlay] {
  animation: altcha-overlay-slidein 0.5s forwards;
  left: 50%;
  position: fixed;
  top: 45%;
  transform: translate(-50%, -50%);
  width: var(--altcha-max-width);
  z-index: var(--altcha-z-index);
}
.altcha-popover[data-display=bottomsheet] {
  animation: altcha-bottomsheet-slideup 0.5s forwards;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  border-bottom: 0;
  bottom: -100%;
  left: 50%;
  position: fixed;
  top: auto;
  transform: translate(-50%, 0);
  width: var(--altcha-max-width);
  z-index: var(--altcha-z-index);
}
.altcha-popover[data-display=bottomsheet] .altcha-popover-content {
  padding-bottom: calc(var(--altcha-padding) * 2);
}
.altcha-popover-backdrop {
  background: var(--altcha-color-base-content);
  bottom: 0;
  left: 0;
  opacity: 0.1;
  position: fixed;
  right: 0;
  top: 0;
  transition: opacity 0.5s;
  z-index: var(--altcha-z-index);
}
.altcha-popover-close {
  color: var(--altcha-color-base-content);
  cursor: pointer;
  display: inline-block;
  font-size: 1rem;
  height: 1.25rem;
  line-height: 0.95;
  position: absolute;
  right: 0;
  text-align: center;
  text-shadow: 0 0 1px var(--altcha-color-base);
  top: -1.5rem;
  width: 1.25rem;
  z-index: var(--altcha-z-index);
}
[dir=rtl] .altcha-popover {
  left: auto;
  right: calc(var(--altcha-padding) / 2);
}
[dir=rtl] .altcha-popover-arrow {
  left: auto;
  right: calc(var(--altcha-checkbox-size) / 2);
}
[dir=rtl] .altcha-popover-close {
  left: 0;
  right: auto;
}
.altcha-popover[data-display=bottomsheet] .altcha-footer, .altcha-popover[data-display=overlay] .altcha-footer {
  align-items: center;
  justify-content: center;
  padding-top: 1rem;
  gap: 0.5rem;
}
.altcha-popover[data-display=bottomsheet] .altcha-footer svg, .altcha-popover[data-display=overlay] .altcha-footer svg {
  height: 18px;
  width: 18px;
  vertical-align: middle;
}
.altcha-code-challenge > form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.altcha-code-challenge-title {
  font-weight: 600;
}
.altcha-code-challenge-text {
  font-size: 0.85rem;
}
.altcha-code-challenge-image {
  background: white;
  border: var(--altcha-input-border-width) solid var(--altcha-color-neutral);
  border-radius: var(--altcha-input-border-radius);
  object-fit: contain;
  height: 50px;
}
.altcha-code-challenge-row {
  display: flex;
  gap: 0.5rem;
}
.altcha-code-challenge-buttons {
  align-items: center;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: var(--altcha-padding);
  justify-content: space-between;
}
.altcha-code-challenge-buttons button {
  justify-content: center;
  width: 100%;
}
.altcha-checkbox {
  cursor: pointer;
  height: var(--altcha-checkbox-size);
  position: relative;
  width: var(--altcha-checkbox-size);
}
.altcha-checkbox input {
  appearance: none;
  background: var(--altcha-input-background-color);
  border: var(--altcha-checkbox-border-width, 2px) solid var(--altcha-checkbox-border-color);
  border-radius: var(--altcha-checkbox-border-radius);
  cursor: pointer;
  height: var(--altcha-checkbox-size);
  left: 0;
  margin: 0;
  padding: 0;
  position: absolute;
  top: 0;
  width: var(--altcha-checkbox-size);
}
@supports (hanging-punctuation: first) and (font: -apple-system-body) and (-webkit-appearance: none) {
  .altcha-checkbox input {
    /* Safari-only: fixes focus outline */
  }
  .altcha-checkbox input:focus {
    outline-width: 2px;
    outline-style: solid;
  }
}
.altcha-checkbox input:before {
  border-radius: var(--altcha-checkbox-border-radius);
  content: "";
  width: 100%;
  height: 100%;
  background: var(--altcha-color-neutral);
  display: block;
  transform: scale(0);
}
.altcha-checkbox input:checked {
  background-color: var(--altcha-color-success);
  border-color: var(--altcha-color-success);
}
.altcha-checkbox input:checked::before {
  background-color: var(--altcha-color-success);
  opacity: 0;
  transform: scale(2.2);
  transition: all var(--altcha-checkbox-transition-duration) ease;
  transition-delay: 0.1s;
}
.altcha-checkbox svg {
  --altcha-radio-svg-size: calc(var(--altcha-checkbox-size) * 0.5);
  --altcha-radio-svg-offset: calc(var(--altcha-checkbox-size) * 0.25);
  fill: none;
  left: var(--altcha-radio-svg-offset);
  height: var(--altcha-radio-svg-size);
  opacity: 0;
  position: absolute;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-dasharray: 16px;
  stroke-dashoffset: 16px;
  top: var(--altcha-radio-svg-offset);
  transform: translate3d(0, 0, 0);
  width: var(--altcha-radio-svg-size);
}
.altcha-checkbox input:checked + svg {
  color: var(--altcha-color-success-content);
  opacity: 1;
  stroke-dashoffset: 0;
  transition: all var(--altcha-checkbox-transition-duration) ease;
  transition-delay: 0.1s;
}
.altcha-checkbox-spinner {
  display: none;
  left: 0;
  height: var(--altcha-checkbox-size);
  position: absolute;
  top: 0;
  width: var(--altcha-checkbox-size);
}
.altcha-checkbox[data-loading=true] input {
  appearance: none;
  opacity: 0;
  pointer-events: none;
}
.altcha-checkbox[data-loading=true] .altcha-checkbox-spinner {
  display: block;
}
.altcha-checkbox-native {
  height: var(--altcha-checkbox-size);
  position: relative;
  width: var(--altcha-checkbox-size);
}
.altcha-checkbox-native input {
  height: var(--altcha-checkbox-size);
  margin: 0;
  width: var(--altcha-checkbox-size);
}
.altcha-checkbox-native-spinner {
  display: none;
  left: 0;
  height: var(--altcha-checkbox-size);
  position: absolute;
  top: 0;
  width: var(--altcha-checkbox-size);
}
.altcha-checkbox-native[data-loading=true] input {
  appearance: none;
  opacity: 0;
  pointer-events: none;
}
.altcha-checkbox-native[data-loading=true] .altcha-checkbox-native-spinner {
  display: block;
}
.altcha-switch {
  align-items: center;
  border-radius: var(--altcha-switch-border-radius);
  background-color: var(--altcha-switch-background-color);
  display: flex;
  height: var(--altcha-switch-height);
  padding: var(--altcha-switch-padding);
  position: relative;
  width: var(--altcha-switch-width);
}
.altcha-switch:focus-within {
  outline: var(--altcha-checkbox-outline);
  outline-offset: var(--altcha-checkbox-outline-offset);
}
.altcha-switch input {
  appearance: none;
  cursor: pointer;
  height: 100%;
  left: 0;
  opacity: 0;
  position: absolute;
  top: 0;
  width: 100%;
}
.altcha-switch-toggle {
  align-items: center;
  background-color: var(--altcha-switch-toggle-color);
  border-radius: var(--altcha-switch-toggle-border-radius);
  cursor: pointer;
  display: flex;
  height: var(--altcha-switch-toggle-size);
  justify-content: center;
  left: var(--altcha-switch-padding);
  position: absolute;
  transition: width 150ms ease-out, left 150ms ease-out;
  width: var(--altcha-switch-toggle-size);
}
.altcha-switch-spinner {
  display: none;
  height: var(--altcha-switch-toggle-size);
  width: var(--altcha-switch-toggle-size);
}
.altcha-switch[data-loading=true] {
  pointer-events: none;
}
.altcha-switch[data-loading=true] .altcha-switch-spinner {
  display: block;
}
.altcha-switch[data-loading=true] .altcha-switch-toggle {
  background-color: transparent;
  left: calc(50% - var(--altcha-switch-toggle-size) / 2);
}
[data-state=verified] .altcha-switch {
  --altcha-switch-background-color: var(--altcha-color-success);
}
[data-state=verified] .altcha-switch-toggle {
  background-color: var(--altcha-color-success-content);
  left: calc(100% - var(--altcha-switch-height) + var(--altcha-switch-padding));
}
[dir=rtl] .altcha-switch-toggle {
  left: calc(100% - var(--altcha-switch-height) + var(--altcha-switch-padding));
}
[dir=rtl][data-state=verified] .altcha-switch-toggle {
  left: var(--altcha-switch-padding);
}
.altcha-floating-arrow {
  border: 6px solid transparent;
  border-bottom-color: var(--altcha-border-color);
  content: "";
  height: 0;
  left: 12px;
  position: absolute;
  top: -12px;
  width: 0;
}
.altcha-overlay-backdrop {
  bottom: 0;
  left: 0;
  position: fixed;
  right: 0;
  top: 0;
  transition: opacity var(--altcha-transition-duration);
  z-index: var(--altcha-z-index);
}
.altcha-overlay-close {
  display: inline-block;
  color: currentColor;
  cursor: pointer;
  font-size: 1rem;
  height: 1rem;
  line-height: 0.85;
  position: absolute;
  right: 0;
  text-align: center;
  text-shadow: 0 0 1px var(--altcha-color-base);
  top: -1.5rem;
  width: 1rem;
  z-index: var(--altcha-z-index);
}
.altcha[data-display=overlay] {
  animation: altcha-overlay-slidein var(--altcha-transition-duration) forwards;
  filter: var(--altcha-shadow);
  left: 50%;
  opacity: 0;
  position: fixed;
  top: 45%;
  transform: translate(-50%, -50%);
  z-index: var(--altcha-z-index);
}
.altcha[data-display=overlay] .altcha-main {
  width: var(--altcha-max-width);
}
.altcha[data-display=floating] {
  display: none;
  filter: var(--altcha-shadow);
  left: var(--altcha-floating-left, -100%);
  position: fixed;
  top: var(--altcha-floating-top, -100%);
  z-index: var(--altcha-z-index);
}
.altcha[data-display=floating] .altcha-main {
  width: var(--altcha-max-width);
}
.altcha[data-display=floating][data-floating-position=top] .altcha-floating-arrow {
  border-bottom-color: transparent;
  border-top-color: var(--altcha-border-color);
  bottom: -12px;
  top: auto;
}
.altcha[data-display=floating][data-visible] {
  display: flex;
}
.altcha[data-display=bar] {
  bottom: -100%;
  filter: var(--altcha-shadow);
  left: 0;
  position: fixed;
  right: 0;
  transition: bottom var(--altcha-transition-duration), top var(--altcha-transition-duration);
  z-index: var(--altcha-z-index);
}
.altcha[data-display=bar] .altcha-main {
  align-items: center;
  border-radius: 0;
  border-width: var(--altcha-border-width) 0 0 0;
  flex-direction: row;
  max-width: 100% !important;
}
.altcha[data-display=bar] .altcha-main > * {
  width: auto;
}
.altcha[data-display=bar][data-placement=top] {
  bottom: auto;
  top: -100%;
}
.altcha[data-display=bar][data-placement=top] .altcha-main {
  border-width: 0 0 var(--altcha-border-width) 0;
}
.altcha[data-display=bar][data-placement=bottom]:not([data-state=unverified]) {
  bottom: 0;
}
.altcha[data-display=bar][data-placement=top]:not([data-state=unverified]) {
  top: 0;
}
.altcha[data-display=invisible] {
  display: none;
}

@keyframes altcha-rotate {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
@keyframes altcha-bottomsheet-slideup {
  100% {
    bottom: 0;
  }
}
@keyframes altcha-overlay-slidein {
  100% {
    opacity: 1;
    top: 50%;
  }
}`;zl(Ql);$altcha.algorithms.set("SHA-256",()=>new Qr);$altcha.algorithms.set("SHA-384",()=>new Qr);$altcha.algorithms.set("SHA-512",()=>new Qr);$altcha.algorithms.set("PBKDF2/SHA-256",()=>new Zr);$altcha.algorithms.set("PBKDF2/SHA-384",()=>new Zr);$altcha.algorithms.set("PBKDF2/SHA-512",()=>new Zr);var Yn=()=>{};function ec(e,t){return e!=e?t==t:e!==t||e!==null&&typeof e=="object"||typeof e=="function"}function tc(e,t,n){if(e==null)return t(void 0),Yn;let r=rc(()=>e.subscribe(t,n));return r.unsubscribe?()=>r.unsubscribe():r}var Yt=[];function nc(e,t=Yn){let n=null,r=new Set;function a(s){if(ec(e,s)&&(e=s,n)){let f=!Yt.length;for(let c of r)c[1](),Yt.push(c,e);if(f){for(let c=0;c<Yt.length;c+=2)Yt[c][0](Yt[c+1]);Yt.length=0}}}function i(s){a(s(e))}function l(s,f=Yn){let c=[s,f];return r.add(c),r.size===1&&(n=t(a,i)||Yn),s(e),()=>{r.delete(c),r.size===0&&n&&(n(),n=null)}}return{set:a,update:i,subscribe:l}}function Kn(e){let t;return tc(e,n=>t=n)(),t}var ea=!1;function rc(e){var t=ea;try{return ea=!0,e()}finally{ea=t}}function qi(e){let t={get:n=>Kn(t.store)[n],set:(n,r)=>{typeof n=="string"?Object.assign(Kn(t.store),{[n]:r}):Object.assign(Kn(t.store),n),t.store.set(Kn(t.store))},store:nc(e)};return t}globalThis.$altcha=globalThis.$altcha||{algorithms:new Map,defaults:qi({}),i18n:qi({}),instances:new Set,plugins:new Set};var ac={ariaLinkLabel:"Altcha (site officiel)",enterCode:"Entrez le code",enterCodeAria:"Entrez le code que vous entendez. Appuyez sur Espace pour \xE9couter l'audio.",error:"\xC9chec de la v\xE9rification. Essayez \xE0 nouveau plus tard.",expired:"La v\xE9rification a expir\xE9. Essayez \xE0 nouveau.",footer:'Prot\xE9g\xE9 par <a href="https://altcha.org/" target="_blank" rel="noopener" aria-label="Altcha (site officiel, s\u2019ouvre dans un nouvel onglet)">ALTCHA</a>',getAudioChallenge:"Obtenir un d\xE9fi audio",label:"Je ne suis pas un robot",loading:"Chargement...",reload:"Recharger",verify:"V\xE9rifier",verificationRequired:"V\xE9rification requise !",verified:"V\xE9rifi\xE9",verifying:"V\xE9rification en cours...",waitAlert:"V\xE9rification en cours... veuillez patienter.",cancel:"Annuler",enterCodeFromImage:"Pour continuer, veuillez entrer le code de l'image ci-dessous."};globalThis.$altcha.i18n.set("fr-fr",ac);})();
//# sourceMappingURL=altcha.js.map
