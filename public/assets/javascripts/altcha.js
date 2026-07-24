(()=>{var Ea=Array.isArray,wo=Array.prototype.indexOf,Vt=Array.prototype.includes,_o=Array.from,Sn=Object.keys,an=Object.defineProperty,Dt=Object.getOwnPropertyDescriptor,ko=Object.getOwnPropertyDescriptors,xo=Object.prototype,Eo=Array.prototype,Sa=Object.getPrototypeOf,oa=Object.isExtensible,ft=()=>{};function So(e){for(var t=0;t<e.length;t++)e[t]()}function Ca(){var e,t,n=new Promise((r,a)=>{e=r,t=a});return{promise:n,resolve:e,reject:t}}var ve=2,Ft=4,In=8,Cr=1<<24,Ve=16,Ze=32,at=64,sr=128,Pe=512,ue=1024,de=2048,Xe=4096,Le=8192,je=16384,gt=32768,lr=1<<25,ht=65536,Cn=1<<17,Co=1<<18,Rt=1<<19,To=1<<20,Tt=65536,Tn=1<<21,Nt=1<<22,dt=1<<23,Ut=Symbol("$state"),$o=Symbol("legacy props"),Ao=Symbol(""),Ta=Symbol("attributes"),cr=Symbol("class"),ur=Symbol("style"),fr=Symbol("text"),en=Symbol("form reset"),On=new class extends Error{name="StaleReactionError";message="The reaction that called `getAbortSignal()` was re-run or destroyed"},sn=!!globalThis.document?.contentType&&globalThis.document.contentType.includes("xml"),ln=3,cn=8;function $a(e){return e===this.v}function Aa(e,t){return e!=e?t==t:e!==t||e!==null&&typeof e=="object"||typeof e=="function"}function Ro(e){return!Aa(e,this.v)}function Io(e){throw new Error("https://svelte.dev/e/lifecycle_outside_component")}function Oo(){throw new Error("https://svelte.dev/e/async_derived_orphan")}function Po(e){throw new Error("https://svelte.dev/e/effect_in_teardown")}function Lo(){throw new Error("https://svelte.dev/e/effect_in_unowned_derived")}function Mo(e){throw new Error("https://svelte.dev/e/effect_orphan")}function Do(){throw new Error("https://svelte.dev/e/effect_update_depth_exceeded")}function No(){throw new Error("https://svelte.dev/e/hydration_failed")}function Uo(){throw new Error("https://svelte.dev/e/state_descriptors_fixed")}function Vo(){throw new Error("https://svelte.dev/e/state_prototype_fixed")}function Fo(){throw new Error("https://svelte.dev/e/state_unsafe_mutation")}function zo(){throw new Error("https://svelte.dev/e/svelte_boundary_reset_onerror")}var jo=1,Ho=2,Tr="[",Ra="[!",sa="[?",Ia="]",$t={},ie=Symbol("uninitialized"),Oa="http://www.w3.org/1999/xhtml",Bo="http://www.w3.org/2000/svg",Ko="http://www.w3.org/1998/Math/MathML",Yo="@attach",ke=null;function zt(e){ke=e}function ot(e,t=!1,n){ke={p:ke,i:!1,c:null,e:null,s:e,x:null,r:A,l:null}}function st(e){var t=ke,n=t.e;if(n!==null){t.e=null;for(var r of n)si(r)}return e!==void 0&&(t.x=e),t.i=!0,ke=t.p,e??{}}function Pa(){return!0}var _t=[];function La(){var e=_t;_t=[],So(e)}function rt(e){if(_t.length===0&&!tn){var t=_t;queueMicrotask(()=>{t===_t&&La()})}_t.push(e)}function Go(){for(;_t.length>0;)La()}function qo(){console.warn("https://svelte.dev/e/derived_inert")}function un(e){console.warn("https://svelte.dev/e/hydration_mismatch")}function Wo(){console.warn("https://svelte.dev/e/select_multiple_invalid_value")}function Jo(){console.warn("https://svelte.dev/e/svelte_boundary_reset_noop")}var R=!1;function Je(e){R=e}var D;function me(e){if(e===null)throw un(),$t;return D=e}function At(){return me(et(D))}function q(e){if(R){if(et(D)!==null)throw un(),$t;D=e}}function $r(e=1){if(R){for(var t=e,n=D;t--;)n=et(n);D=n}}function Ar(e=!0){for(var t=0,n=D;;){if(n.nodeType===cn){var r=n.data;if(r===Ia){if(t===0)return n;t-=1}else(r===Tr||r===Ra||r[0]==="["&&!isNaN(Number(r.slice(1))))&&(t+=1)}var a=et(n);e&&n.remove(),n=a}}function Ma(e){if(!e||e.nodeType!==cn)throw un(),$t;return e.data}function nt(e){if(typeof e!="object"||e===null||Ut in e)return e;let t=Sa(e);if(t!==xo&&t!==Eo)return e;var n=new Map,r=Ea(e),a=O(0),o=Ct,s=l=>{if(Ct===o)return l();var u=U,c=Ct;De(null),va(o);var d=l();return De(u),va(c),d};return r&&n.set("length",O(e.length)),new Proxy(e,{defineProperty(l,u,c){(!("value"in c)||c.configurable===!1||c.enumerable===!1||c.writable===!1)&&Uo();var d=n.get(u);return d===void 0?s(()=>{var v=O(c.value);return n.set(u,v),v}):_(d,c.value,!0),!0},deleteProperty(l,u){var c=n.get(u);if(c===void 0){if(u in l){let d=s(()=>O(ie));n.set(u,d),nn(a)}}else _(c,ie),nn(a);return!0},get(l,u,c){if(u===Ut)return e;var d=n.get(u),v=u in l;if(d===void 0&&(!v||Dt(l,u)?.writable)&&(d=s(()=>{var b=nt(v?l[u]:ie),g=O(b);return g}),n.set(u,d)),d!==void 0){var h=i(d);return h===ie?void 0:h}return Reflect.get(l,u,c)},getOwnPropertyDescriptor(l,u){var c=Reflect.getOwnPropertyDescriptor(l,u);if(c&&"value"in c){var d=n.get(u);d&&(c.value=i(d))}else if(c===void 0){var v=n.get(u),h=v?.v;if(v!==void 0&&h!==ie)return{enumerable:!0,configurable:!0,value:h,writable:!0}}return c},has(l,u){if(u===Ut)return!0;var c=n.get(u),d=c!==void 0&&c.v!==ie||Reflect.has(l,u);if(c!==void 0||A!==null&&(!d||Dt(l,u)?.writable)){c===void 0&&(c=s(()=>{var h=d?nt(l[u]):ie,b=O(h);return b}),n.set(u,c));var v=i(c);if(v===ie)return!1}return d},set(l,u,c,d){var v=n.get(u),h=u in l;if(r&&u==="length")for(var b=c;b<v.v;b+=1){var g=n.get(b+"");g!==void 0?_(g,ie):b in l&&(g=s(()=>O(ie)),n.set(b+"",g))}if(v===void 0)(!h||Dt(l,u)?.writable)&&(v=s(()=>O(void 0)),_(v,nt(c)),n.set(u,v));else{h=v.v!==ie;var E=s(()=>nt(c));_(v,E)}var $=Reflect.getOwnPropertyDescriptor(l,u);if($?.set&&$.set.call(d,c),!h){if(r&&typeof u=="string"){var P=n.get("length"),oe=Number(u);Number.isInteger(oe)&&oe>=P.v&&_(P,oe+1)}nn(a)}return!0},ownKeys(l){i(a);var u=Reflect.ownKeys(l).filter(v=>{var h=n.get(v);return h===void 0||h.v!==ie});for(var[c,d]of n)d.v!==ie&&!(c in l)&&u.push(c);return u},setPrototypeOf(){Vo()}})}function la(e){try{if(e!==null&&typeof e=="object"&&Ut in e)return e[Ut]}catch{}return e}function Zo(e,t){return Object.is(la(e),la(t))}var Et,dr,Da,Na,Ua;function hr(){if(Et===void 0){Et=window,dr=document,Da=/Firefox/.test(navigator.userAgent);var e=Element.prototype,t=Node.prototype,n=Text.prototype;Na=Dt(t,"firstChild").get,Ua=Dt(t,"nextSibling").get,oa(e)&&(e[cr]=void 0,e[Ta]=null,e[ur]=void 0,e.__e=void 0),oa(n)&&(n[fr]=void 0)}}function He(e=""){return document.createTextNode(e)}function Te(e){return Na.call(e)}function et(e){return Ua.call(e)}function Q(e,t){if(!R)return Te(e);var n=Te(D);if(n===null)n=D.appendChild(He());else if(t&&n.nodeType!==ln){var r=He();return n?.before(r),me(r),r}return t&&Pn(n),me(n),n}function Lt(e,t=!1){if(!R){var n=Te(e);return n instanceof Comment&&n.data===""?et(n):n}if(t){if(D?.nodeType!==ln){var r=He();return D?.before(r),me(r),r}Pn(D)}return D}function W(e,t=1,n=!1){let r=R?D:e;for(var a;t--;)a=r,r=et(r);if(!R)return r;if(n){if(r?.nodeType!==ln){var o=He();return r===null?a?.after(o):r.before(o),me(o),o}Pn(r)}return me(r),r}function Xo(e){e.textContent=""}function Qo(){return!1}function Rr(e,t,n){return document.createElementNS(t??Oa,e,void 0)}function Pn(e){if(e.nodeValue.length<65536)return;let t=e.nextSibling;for(;t!==null&&t.nodeType===ln;)t.remove(),e.nodeValue+=t.nodeValue,t=e.nextSibling}function Va(e){var t=A;if(t===null)return U.f|=dt,e;if((t.f&gt)===0&&(t.f&Ft)===0)throw e;ut(e,t)}function ut(e,t){for(;t!==null;){if((t.f&sr)!==0){if((t.f&gt)===0)throw e;try{t.b.error(e);return}catch(n){e=n}}t=t.parent}throw e}var es=-7169;function ne(e,t){e.f=e.f&es|t}function Ir(e){(e.f&Pe)!==0||e.deps===null?ne(e,ue):ne(e,Xe)}function Fa(e){if(e!==null)for(let t of e)(t.f&ve)===0||(t.f&Tt)===0||(t.f^=Tt,Fa(t.deps))}function za(e,t,n){(e.f&de)!==0?t.add(e):(e.f&Xe)!==0&&n.add(e),Fa(e.deps),ne(e,ue)}function ja(e,t,n){if(e==null)return t(void 0),ft;let r=hn(()=>e.subscribe(t,n));return r.unsubscribe?()=>r.unsubscribe():r}var Ot=[];function ts(e,t=ft){let n=null,r=new Set;function a(l){if(Aa(e,l)&&(e=l,n)){let u=!Ot.length;for(let c of r)c[1](),Ot.push(c,e);if(u){for(let c=0;c<Ot.length;c+=2)Ot[c][0](Ot[c+1]);Ot.length=0}}}function o(l){a(l(e))}function s(l,u=ft){let c=[l,u];return r.add(c),r.size===1&&(n=t(a,o)||ft),l(e),()=>{r.delete(c),r.size===0&&n&&(n(),n=null)}}return{set:a,update:o,subscribe:s}}function Xt(e){let t;return ja(e,n=>t=n)(),t}var vr=Symbol("unmounted");function ca(e,t,n){let r=n[t]??={store:null,source:Za(void 0),unsubscribe:ft};if(r.store!==e&&!(vr in n))if(r.unsubscribe(),r.store=e??null,e==null)r.source.v=void 0,r.unsubscribe=ft;else{var a=!0;r.unsubscribe=ja(e,o=>{a?r.source.v=o:_(r.source,o)}),a=!1}return e&&vr in n?Xt(e):i(r.source)}function ns(){let e={};function t(){Mn(()=>{for(var n in e)e[n].unsubscribe();an(e,vr,{enumerable:!1,value:!0})})}return[e,t]}var er=null,Pt=null,I=null,pr=null,Fe=null,gr=null,tn=!1,tr=!1,Mt=null,kn=null,ua=0;var rs=1,vt=class e{id=rs++;#e=!1;linked=!0;#t=null;#n=null;async_deriveds=new Map;current=new Map;previous=new Map;unblocked=new Set;#l=new Set;#r=new Set;#i=new Set;#a=0;#o=new Map;#d=null;#s=[];#v=[];#h=new Set;#c=new Set;#u=new Map;#f=new Set;is_fork=!1;#b=!1;#_(){if(this.is_fork)return!0;for(let r of this.#o.keys()){for(var t=r,n=!1;t.parent!==null;){if(this.#u.has(t)){n=!0;break}t=t.parent}if(!n)return!0}return!1}skip_effect(t){this.#u.has(t)||this.#u.set(t,{d:[],m:[]}),this.#f.delete(t)}unskip_effect(t,n=r=>this.schedule(r)){var r=this.#u.get(t);if(r){this.#u.delete(t);for(var a of r.d)ne(a,de),n(a);for(a of r.m)ne(a,Xe),n(a)}this.#f.add(t)}#g(){if(this.#e=!0,ua++>1e3&&(this.#w(),as()),!this.#_()){for(let u of this.#h)this.#c.delete(u),ne(u,de),this.schedule(u);for(let u of this.#c)ne(u,Xe),this.schedule(u)}let t=this.#s;this.#s=[],this.apply();var n=Mt=[],r=[],a=kn=[];for(let u of t)try{this.#k(u,n,r)}catch(c){throw Ka(u),c}if(I=null,a.length>0){var o=e.ensure();for(let u of a)o.schedule(u)}if(Mt=null,kn=null,this.#_()){this.#p(r),this.#p(n);for(let[u,c]of this.#u)Ba(u,c);a.length>0&&I.#g();return}let s=this.#x();if(s){s.#m(this);return}this.#h.clear(),this.#c.clear();for(let u of this.#l)u(this);this.#l.clear(),pr=this,fa(r),fa(n),pr=null,this.#d?.resolve();var l=I;if(this.linked&&this.#a===0&&this.#w(),this.#s.length>0){l===null&&(l=this,this.#y());let u=l;u.#s.push(...this.#s.filter(c=>!u.#s.includes(c)))}l!==null&&l.#g()}#k(t,n,r){t.f^=ue;for(var a=t.first;a!==null;){var o=a.f,s=(o&(Ze|at))!==0,l=s&&(o&ue)!==0,u=l||(o&Le)!==0||this.#u.has(a);if(!u&&a.fn!==null){s?a.f^=ue:(o&Ft)!==0?n.push(a):dn(a)&&((o&Ve)!==0&&this.#c.add(a),jt(a));var c=a.first;if(c!==null){a=c;continue}}for(;a!==null;){var d=a.next;if(d!==null){a=d;break}a=a.parent}}}#x(){for(var t=this.#t;t!==null;){if(!t.is_fork){for(let[n,[,r]]of this.current)if(t.current.has(n)&&!r)return t}t=t.#t}return null}#m(t){for(let[r,a]of t.current)!this.previous.has(r)&&t.previous.has(r)&&this.previous.set(r,t.previous.get(r)),this.current.set(r,a);for(let[r,a]of t.async_deriveds){let o=this.async_deriveds.get(r);o&&a.promise.then(o.resolve)}let n=r=>{var a=r.reactions;if(a!==null)for(let l of a){var o=l.f;if((o&ve)!==0)n(l);else{var s=l;o&(Nt|Ve)&&!this.async_deriveds.has(s)&&(this.#c.delete(s),ne(s,de),this.schedule(s))}}};for(let r of this.current.keys())n(r);this.oncommit(()=>t.discard()),t.#w(),I=this,this.#g()}#p(t){for(var n=0;n<t.length;n+=1)za(t[n],this.#h,this.#c)}capture(t,n,r=!1){t.v!==ie&&!this.previous.has(t)&&this.previous.set(t,t.v),(t.f&dt)===0&&(this.current.set(t,[n,r]),Fe?.set(t,n)),this.is_fork||(t.v=n)}activate(){I=this}deactivate(){I=null,Fe=null}flush(){try{tr=!0,I=this,this.#g()}finally{ua=0,gr=null,Mt=null,kn=null,tr=!1,I=null,Fe=null,St.clear()}}discard(){for(let t of this.#r)t(this);this.#r.clear(),this.#i.clear(),this.#w()}register_created_effect(t){this.#v.push(t)}#E(){this.#w();for(let d=er;d!==null;d=d.#n){var t=d.id<this.id,n=[];for(let[v,[h,b]]of this.current){if(d.current.has(v)){var r=d.current.get(v)[0];if(t&&h!==r)d.current.set(v,[h,b]);else continue}n.push(v)}if(t)for(let[v,h]of this.async_deriveds){let b=d.async_deriveds.get(v);b&&h.promise.then(b.resolve)}if(d.#e){var a=[...d.current.keys()].filter(v=>!this.current.has(v));if(a.length===0)t&&d.discard();else if(n.length>0){if(t)for(let v of this.#f)d.unskip_effect(v,h=>{(h.f&(Ve|Nt))!==0?d.schedule(h):d.#p([h])});d.activate();var o=new Set,s=new Map;for(var l of n)Ha(l,a,o,s);s=new Map;var u=[...d.current.keys()].filter(v=>this.current.has(v)?this.current.get(v)[0]!==v.v:!0);if(u.length>0)for(let v of this.#v)(v.f&(je|Le|Cn))===0&&Or(v,u,s)&&((v.f&(Nt|Ve))!==0?(ne(v,de),d.schedule(v)):d.#h.add(v));if(d.#s.length>0){d.apply();for(var c of d.#s)d.#k(c,[],[]);d.#s=[]}d.deactivate()}}}}increment(t,n){if(this.#a+=1,t){let r=this.#o.get(n)??0;this.#o.set(n,r+1)}}decrement(t,n){if(this.#a-=1,t){let r=this.#o.get(n)??0;r===1?this.#o.delete(n):this.#o.set(n,r-1)}this.#b||(this.#b=!0,rt(()=>{this.#b=!1,this.linked&&this.flush()}))}transfer_effects(t,n){for(let r of t)this.#h.add(r);for(let r of n)this.#c.add(r);t.clear(),n.clear()}oncommit(t){this.#l.add(t)}ondiscard(t){this.#r.add(t)}on_fork_commit(t){this.#i.add(t)}run_fork_commit_callbacks(){for(let t of this.#i)t(this);this.#i.clear()}settled(){return(this.#d??=Ca()).promise}static ensure(){if(I===null){let t=I=new e;t.#y(),!tr&&!tn&&rt(()=>{t.#e||t.flush()})}return I}apply(){{Fe=null;return}}schedule(t){if(gr=t,t.b?.is_pending&&(t.f&(Ft|In|Cr))!==0&&(t.f&gt)===0){t.b.defer_effect(t);return}for(var n=t;n.parent!==null;){n=n.parent;var r=n.f;if(Mt!==null&&n===A&&(U===null||(U.f&ve)===0))return;if((r&(at|Ze))!==0){if((r&ue)===0)return;n.f^=ue}}this.#s.push(n)}#y(){Pt===null?er=Pt=this:(Pt.#n=this,this.#t=Pt),Pt=this}#w(){var t=this.#t,n=this.#n;t===null?er=n:t.#n=n,n===null?Pt=t:n.#t=t,this.linked=!1}};function Y(e){var t=tn;tn=!0;try{for(var n;;){if(Go(),I===null)return n;I.flush()}}finally{tn=t}}function as(){try{Do()}catch(e){ut(e,gr)}}var tt=null;function fa(e){var t=e.length;if(t!==0){for(var n=0;n<t;){var r=e[n++];if((r.f&(je|Le))===0&&dn(r)&&(tt=new Set,jt(r),r.deps===null&&r.first===null&&r.nodes===null&&r.teardown===null&&r.ac===null&&fi(r),tt?.size>0)){St.clear();for(let a of tt){if((a.f&(je|Le))!==0)continue;let o=[a],s=a.parent;for(;s!==null;)tt.has(s)&&(tt.delete(s),o.push(s)),s=s.parent;for(let l=o.length-1;l>=0;l--){let u=o[l];(u.f&(je|Le))===0&&jt(u)}}tt.clear()}}tt=null}}function Ha(e,t,n,r){if(!n.has(e)&&(n.add(e),e.reactions!==null))for(let a of e.reactions){let o=a.f;(o&ve)!==0?Ha(a,t,n,r):(o&(Nt|Ve))!==0&&(o&de)===0&&Or(a,t,r)&&(ne(a,de),Pr(a))}}function Or(e,t,n){let r=n.get(e);if(r!==void 0)return r;if(e.deps!==null)for(let a of e.deps){if(Vt.call(t,a))return!0;if((a.f&ve)!==0&&Or(a,t,n))return n.set(a,!0),!0}return n.set(e,!1),!1}function Pr(e){I.schedule(e)}function Ba(e,t){if(!((e.f&Ze)!==0&&(e.f&ue)!==0)){(e.f&de)!==0?t.d.push(e):(e.f&Xe)!==0&&t.m.push(e),ne(e,ue);for(var n=e.first;n!==null;)Ba(n,t),n=n.next}}function Ka(e){ne(e,ue);for(var t=e.first;t!==null;)Ka(t),t=t.next}function is(e){let t=0,n=fn(0),r;return()=>{Dr()&&(i(n),Dn(()=>(t===0&&(r=hn(()=>e(()=>nn(n)))),t+=1,()=>{rt(()=>{t-=1,t===0&&(r?.(),r=void 0,nn(n))})})))}}var os=ht|Rt;function ss(e,t,n,r){new br(e,t,n,r)}var br=class{parent;is_pending=!1;transform_error;#e;#t=R?D:null;#n;#l;#r;#i=null;#a=null;#o=null;#d=null;#s=0;#v=0;#h=!1;#c=new Set;#u=new Set;#f=null;#b=is(()=>(this.#f=fn(this.#s),()=>{this.#f=null}));constructor(t,n,r,a){this.#e=t,this.#n=n,this.#l=o=>{var s=A;s.b=this,s.f|=sr,r(o)},this.parent=A.b,this.transform_error=a??this.parent?.transform_error??(o=>o),this.#r=vn(()=>{if(R){let o=this.#t;At();let s=o.data===Ra;if(o.data.startsWith(sa)){let u=JSON.parse(o.data.slice(sa.length));this.#g(u)}else s?this.#k():this.#_()}else this.#x()},os),R&&(this.#e=D)}#_(){try{this.#i=Ue(()=>this.#l(this.#e))}catch(t){this.error(t)}}#g(t){let n=this.#n.failed;n&&(this.#o=Ue(()=>{n(this.#e,()=>t,()=>()=>{})}))}#k(){let t=this.#n.pending;t&&(this.is_pending=!0,this.#a=Ue(()=>t(this.#e)),rt(()=>{var n=this.#d=document.createDocumentFragment(),r=He();n.append(r),this.#i=this.#p(()=>Ue(()=>this.#l(r))),this.#v===0&&(this.#e.before(n),this.#d=null,rn(this.#a,()=>{this.#a=null}),this.#m(I))}))}#x(){try{if(this.is_pending=this.has_pending_snippet(),this.#v=0,this.#s=0,this.#i=Ue(()=>{this.#l(this.#e)}),this.#v>0){var t=this.#d=document.createDocumentFragment();vi(this.#i,t);let n=this.#n.pending;this.#a=Ue(()=>n(this.#e))}else this.#m(I)}catch(n){this.error(n)}}#m(t){this.is_pending=!1,t.transfer_effects(this.#c,this.#u)}defer_effect(t){za(t,this.#c,this.#u)}is_rendered(){return!this.is_pending&&(!this.parent||this.parent.is_rendered())}has_pending_snippet(){return!!this.#n.pending}#p(t){var n=A,r=U,a=ke;Qe(this.#r),De(this.#r),zt(this.#r.ctx);try{return vt.ensure(),t()}catch(o){return Va(o),null}finally{Qe(n),De(r),zt(a)}}#E(t,n){if(!this.has_pending_snippet()){this.parent&&this.parent.#E(t,n);return}this.#v+=t,this.#v===0&&(this.#m(n),this.#a&&rn(this.#a,()=>{this.#a=null}),this.#d&&(this.#e.before(this.#d),this.#d=null))}update_pending_count(t,n){this.#E(t,n),this.#s+=t,!(!this.#f||this.#h)&&(this.#h=!0,rt(()=>{this.#h=!1,this.#f&&Rn(this.#f,this.#s)}))}get_effect_pending(){return this.#b(),i(this.#f)}error(t){if(!this.#n.onerror&&!this.#n.failed)throw t;I?.is_fork?(this.#i&&I.skip_effect(this.#i),this.#a&&I.skip_effect(this.#a),this.#o&&I.skip_effect(this.#o),I.on_fork_commit(()=>{this.#y(t)})):this.#y(t)}#y(t){this.#i&&(he(this.#i),this.#i=null),this.#a&&(he(this.#a),this.#a=null),this.#o&&(he(this.#o),this.#o=null),R&&(me(this.#t),$r(),me(Ar()));var n=this.#n.onerror;let r=this.#n.failed;var a=!1,o=!1;let s=()=>{if(a){Jo();return}a=!0,o&&zo(),this.#o!==null&&rn(this.#o,()=>{this.#o=null}),this.#p(()=>{this.#x()})},l=u=>{try{o=!0,n?.(u,s),o=!1}catch(c){ut(c,this.#r&&this.#r.parent)}r&&(this.#o=this.#p(()=>{try{return Ue(()=>{var c=A;c.b=this,c.f|=sr,r(this.#e,()=>u,()=>s)})}catch(c){return ut(c,this.#r.parent),null}}))};rt(()=>{var u;try{u=this.transform_error(t)}catch(c){ut(c,this.#r&&this.#r.parent);return}u!==null&&typeof u=="object"&&typeof u.then=="function"?u.then(l,c=>ut(c,this.#r&&this.#r.parent)):l(u)})}};function Ya(e,t,n,r){let a=Lr;var o=e.filter(h=>!h.settled);if(n.length===0&&o.length===0){r(t.map(a));return}var s=A,l=ls(),u=o.length===1?o[0].promise:o.length>1?Promise.all(o.map(h=>h.promise)):null;function c(h){if((s.f&je)===0){l();try{r(h)}catch(b){ut(b,s)}$n()}}var d=Ga();if(n.length===0){u.then(()=>c(t.map(a))).finally(d);return}function v(){Promise.all(n.map(h=>cs(h))).then(h=>c([...t.map(a),...h])).catch(h=>ut(h,s)).finally(d)}u?u.then(()=>{l(),v(),$n()}):v()}function ls(){var e=A,t=U,n=ke,r=I;return function(o=!0){Qe(e),De(t),zt(n),o&&(e.f&je)===0&&(r?.activate(),r?.apply())}}function $n(e=!0){Qe(null),De(null),zt(null),e&&I?.deactivate()}function Ga(){var e=A,t=e.b,n=I,r=t.is_rendered();return t.update_pending_count(1,n),n.increment(r,e),()=>{t.update_pending_count(-1,n),n.decrement(r,e)}}function Lr(e){var t=ve|de;return A!==null&&(A.f|=Rt),{ctx:ke,deps:null,effects:null,equals:$a,f:t,fn:e,reactions:null,rv:0,v:ie,wv:0,parent:A,ac:null}}var yn=Symbol("obsolete");function cs(e,t,n){let r=A;r===null&&Oo();var a=void 0,o=fn(ie),s=!U,l=new Set;return _s(()=>{var u=A,c=Ca();a=c.promise;try{Promise.resolve(e()).then(c.resolve,b=>{b!==On&&c.reject(b)}).finally($n)}catch(b){c.reject(b),$n()}var d=I;if(s){if((u.f&gt)!==0)var v=Ga();if(r.b.is_rendered())d.async_deriveds.get(u)?.reject(yn);else for(let b of l.values())b.reject(yn);l.add(c),d.async_deriveds.set(u,c)}let h=(b,g=void 0)=>{v?.(),l.delete(c),g!==yn&&(d.activate(),g?(o.f|=dt,Rn(o,g)):((o.f&dt)!==0&&(o.f^=dt),Rn(o,b)),d.deactivate())};c.promise.then(h,b=>h(null,b||"unknown"))}),Mn(()=>{for(let u of l)u.reject(yn)}),new Promise(u=>{function c(d){function v(){d===a?u(o):c(a)}d.then(v,v)}c(a)})}function be(e){let t=Lr(e);return ei(t),t}function us(e){var t=e.effects;if(t!==null){e.effects=null;for(var n=0;n<t.length;n+=1)he(t[n])}}function Mr(e){var t,n=A,r=e.parent;if(!it&&r!==null&&e.v!==ie&&(r.f&(je|Le))!==0)return qo(),e.v;Qe(r);try{e.f&=~Tt,us(e),t=ai(e)}finally{Qe(n)}return t}function qa(e){var t=Mr(e);if(!e.equals(t)&&(e.wv=ni(),(!I?.is_fork||e.deps===null)&&(I!==null?(I.capture(e,t,!0),pr?.capture(e,t,!0)):e.v=t,e.deps===null))){ne(e,ue);return}it||(Fe!==null?(Dr()||I?.is_fork)&&Fe.set(e,t):Ir(e))}function fs(e){if(e.effects!==null)for(let t of e.effects)(t.teardown||t.ac)&&(t.teardown?.(),t.ac?.abort(On),t.fn!==null&&(t.teardown=ft),t.ac=null,on(t,0),Ur(t))}function Wa(e){if(e.effects!==null)for(let t of e.effects)t.teardown&&t.fn!==null&&jt(t)}var An=new Set,St=new Map,Ja=!1;function fn(e,t){var n={f:0,v:e,reactions:null,equals:$a,rv:0,wv:0};return n}function O(e,t){let n=fn(e);return ei(n),n}function Za(e,t=!1,n=!0){let r=fn(e);return t||(r.equals=Ro),r}function _(e,t,n=!1){U!==null&&(!ze||(U.f&Cn)!==0)&&Pa()&&(U.f&(ve|Ve|Nt|Cn))!==0&&(Me===null||!Vt.call(Me,e))&&Fo();let r=n?nt(t):t;return Rn(e,r,kn)}function Rn(e,t,n=null){if(!e.equals(t)){St.set(e,it?t:e.v);var r=vt.ensure();if(r.capture(e,t),(e.f&ve)!==0){let a=e;(e.f&de)!==0&&Mr(a),Fe===null&&Ir(a)}e.wv=ni(),Xa(e,de,n),A!==null&&(A.f&ue)!==0&&(A.f&(Ze|at))===0&&(Oe===null?ps([e]):Oe.push(e)),!r.is_fork&&An.size>0&&!Ja&&ds()}return t}function ds(){Ja=!1;for(let e of An){(e.f&ue)!==0&&ne(e,Xe);let t;try{t=dn(e)}catch{t=!0}t&&jt(e)}An.clear()}function nn(e){_(e,e.v+1)}function Xa(e,t,n){var r=e.reactions;if(r!==null)for(var a=r.length,o=0;o<a;o++){var s=r[o],l=s.f,u=(l&de)===0;if(u&&ne(s,t),(l&Cn)!==0)An.add(s);else if((l&ve)!==0){var c=s;Fe?.delete(c),(l&Tt)===0&&(l&Pe&&(A===null||(A.f&Tn)===0)&&(s.f|=Tt),Xa(c,Xe,n))}else if(u){var d=s;(l&Ve)!==0&&tt!==null&&tt.add(d),n!==null?n.push(d):Pr(d)}}}function hs(e,t){if(t){let n=document.body;e.autofocus=!0,rt(()=>{document.activeElement===n&&e.focus()})}}var da=!1;function Qa(){da||(da=!0,document.addEventListener("reset",e=>{Promise.resolve().then(()=>{if(!e.defaultPrevented)for(let t of e.target.elements)t[en]?.()})},{capture:!0}))}function Ln(e){var t=U,n=A;De(null),Qe(null);try{return e()}finally{De(t),Qe(n)}}function vs(e,t,n,r=n){e.addEventListener(t,()=>Ln(n));let a=e[en];a?e[en]=()=>{a(),r(!0)}:e[en]=()=>r(!0),Qa()}var xn=!1,it=!1;function ha(e){it=e}var U=null,ze=!1;function De(e){U=e}var A=null;function Qe(e){A=e}var Me=null;function ei(e){U!==null&&(Me===null?Me=[e]:Me.push(e))}var _e=null,Se=0,Oe=null;function ps(e){Oe=e}var ti=1,kt=0,Ct=kt;function va(e){Ct=e}function ni(){return++ti}function dn(e){var t=e.f;if((t&de)!==0)return!0;if(t&ve&&(e.f&=~Tt),(t&Xe)!==0){for(var n=e.deps,r=n.length,a=0;a<r;a++){var o=n[a];if(dn(o)&&qa(o),o.wv>e.wv)return!0}(t&Pe)!==0&&Fe===null&&ne(e,ue)}return!1}function ri(e,t,n=!0){var r=e.reactions;if(r!==null&&!(Me!==null&&Vt.call(Me,e)))for(var a=0;a<r.length;a++){var o=r[a];(o.f&ve)!==0?ri(o,t,!1):t===o&&(n?ne(o,de):(o.f&ue)!==0&&ne(o,Xe),Pr(o))}}function ai(e){var t=_e,n=Se,r=Oe,a=U,o=Me,s=ke,l=ze,u=Ct,c=e.f;_e=null,Se=0,Oe=null,U=(c&(Ze|at))===0?e:null,Me=null,zt(e.ctx),ze=!1,Ct=++kt,e.ac!==null&&(Ln(()=>{e.ac.abort(On)}),e.ac=null);try{e.f|=Tn;var d=e.fn,v=d();e.f|=gt;var h=e.deps,b=I?.is_fork;if(_e!==null){var g;if(b||on(e,Se),h!==null&&Se>0)for(h.length=Se+_e.length,g=0;g<_e.length;g++)h[Se+g]=_e[g];else e.deps=h=_e;if(Dr()&&(e.f&Pe)!==0)for(g=Se;g<h.length;g++)(h[g].reactions??=[]).push(e)}else!b&&h!==null&&Se<h.length&&(on(e,Se),h.length=Se);if(Pa()&&Oe!==null&&!ze&&h!==null&&(e.f&(ve|Xe|de))===0)for(g=0;g<Oe.length;g++)ri(Oe[g],e);if(a!==null&&a!==e){if(kt++,a.deps!==null)for(let E=0;E<n;E+=1)a.deps[E].rv=kt;if(t!==null)for(let E of t)E.rv=kt;Oe!==null&&(r===null?r=Oe:r.push(...Oe))}return(e.f&dt)!==0&&(e.f^=dt),v}catch(E){return Va(E)}finally{e.f^=Tn,_e=t,Se=n,Oe=r,U=a,Me=o,zt(s),ze=l,Ct=u}}function gs(e,t){let n=t.reactions;if(n!==null){var r=wo.call(n,e);if(r!==-1){var a=n.length-1;a===0?n=t.reactions=null:(n[r]=n[a],n.pop())}}if(n===null&&(t.f&ve)!==0&&(_e===null||!Vt.call(_e,t))){var o=t;(o.f&Pe)!==0&&(o.f^=Pe,o.f&=~Tt),o.v!==ie&&Ir(o),fs(o),on(o,0)}}function on(e,t){var n=e.deps;if(n!==null)for(var r=t;r<n.length;r++)gs(e,n[r])}function jt(e){var t=e.f;if((t&je)===0){ne(e,ue);var n=A,r=xn;A=e,xn=!0;try{(t&(Ve|Cr))!==0?ks(e):Ur(e),ci(e);var a=ai(e);e.teardown=typeof a=="function"?a:null,e.wv=ti;var o}finally{xn=r,A=n}}}async function xt(){await Promise.resolve(),Y()}function i(e){var t=e.f,n=(t&ve)!==0;if(U!==null&&!ze){var r=A!==null&&(A.f&je)!==0;if(!r&&(Me===null||!Vt.call(Me,e))){var a=U.deps;if((U.f&Tn)!==0)e.rv<kt&&(e.rv=kt,_e===null&&a!==null&&a[Se]===e?Se++:_e===null?_e=[e]:_e.push(e));else{(U.deps??=[]).push(e);var o=e.reactions;o===null?e.reactions=[U]:Vt.call(o,U)||o.push(U)}}}if(it&&St.has(e))return St.get(e);if(n){var s=e;if(it){var l=s.v;return((s.f&ue)===0&&s.reactions!==null||oi(s))&&(l=Mr(s)),St.set(s,l),l}var u=(s.f&Pe)===0&&!ze&&U!==null&&(xn||(U.f&Pe)!==0),c=(s.f&gt)===0;dn(s)&&(u&&(s.f|=Pe),qa(s)),u&&!c&&(Wa(s),ii(s))}if(Fe?.has(e))return Fe.get(e);if((e.f&dt)!==0)throw e.v;return e.v}function ii(e){if(e.f|=Pe,e.deps!==null)for(let t of e.deps)(t.reactions??=[]).push(e),(t.f&ve)!==0&&(t.f&Pe)===0&&(Wa(t),ii(t))}function oi(e){if(e.v===ie)return!0;if(e.deps===null)return!1;for(let t of e.deps)if(St.has(t)||(t.f&ve)!==0&&oi(t))return!0;return!1}function hn(e){var t=ze;try{return ze=!0,e()}finally{ze=t}}function bs(e){A===null&&(U===null&&Mo(),Lo()),it&&Po()}function ms(e,t){var n=t.last;n===null?t.last=t.first=e:(n.next=e,e.prev=n,t.last=e)}function Be(e,t){var n=A;n!==null&&(n.f&Le)!==0&&(e|=Le);var r={ctx:ke,deps:null,nodes:null,f:e|de|Pe,first:null,fn:t,last:null,next:null,parent:n,b:n&&n.b,prev:null,teardown:null,wv:0,ac:null};I?.register_created_effect(r);var a=r;if((e&Ft)!==0)Mt!==null?Mt.push(r):vt.ensure().schedule(r);else if(t!==null){try{jt(r)}catch(s){throw he(r),s}a.deps===null&&a.teardown===null&&a.nodes===null&&a.first===a.last&&(a.f&Rt)===0&&(a=a.first,(e&Ve)!==0&&(e&ht)!==0&&a!==null&&(a.f|=ht))}if(a!==null&&(a.parent=n,n!==null&&ms(a,n),U!==null&&(U.f&ve)!==0&&(e&at)===0)){var o=U;(o.effects??=[]).push(a)}return r}function Dr(){return U!==null&&!ze}function Mn(e){let t=Be(In,null);return ne(t,ue),t.teardown=e,t}function Ce(e){bs();var t=A.f,n=!U&&(t&Ze)!==0&&(t&gt)===0;if(n){var r=ke;(r.e??=[]).push(e)}else return si(e)}function si(e){return Be(Ft|To,e)}function ys(e){vt.ensure();let t=Be(at|Rt,e);return()=>{he(t)}}function ws(e){vt.ensure();let t=Be(at|Rt,e);return(n={})=>new Promise(r=>{n.outro?rn(t,()=>{he(t),r(void 0)}):(he(t),r(void 0))})}function Nr(e){return Be(Ft,e)}function _s(e){return Be(Nt|Rt,e)}function Dn(e,t=0){return Be(In|t,e)}function pe(e,t=[],n=[],r=[]){Ya(r,t,n,a=>{Be(In,()=>e(...a.map(i)))})}function vn(e,t=0){var n=Be(Ve|t,e);return n}function li(e,t=0){var n=Be(Cr|t,e);return n}function Ue(e){return Be(Ze|Rt,e)}function ci(e){var t=e.teardown;if(t!==null){let n=it,r=U;ha(!0),De(null);try{t.call(null)}finally{ha(n),De(r)}}}function Ur(e,t=!1){var n=e.first;for(e.first=e.last=null;n!==null;){let a=n.ac;a!==null&&Ln(()=>{a.abort(On)});var r=n.next;(n.f&at)!==0?n.parent=null:he(n,t),n=r}}function ks(e){for(var t=e.first;t!==null;){var n=t.next;(t.f&Ze)===0&&he(t),t=n}}function he(e,t=!0){var n=!1;(t||(e.f&Co)!==0)&&e.nodes!==null&&e.nodes.end!==null&&(ui(e.nodes.start,e.nodes.end),n=!0),ne(e,lr),Ur(e,t&&!n),on(e,0);var r=e.nodes&&e.nodes.t;if(r!==null)for(let o of r)o.stop();ci(e),e.f^=lr,e.f|=je;var a=e.parent;a!==null&&a.first!==null&&fi(e),e.next=e.prev=e.teardown=e.ctx=e.deps=e.fn=e.nodes=e.ac=e.b=null}function ui(e,t){for(;e!==null;){var n=e===t?null:et(e);e.remove(),e=n}}function fi(e){var t=e.parent,n=e.prev,r=e.next;n!==null&&(n.next=r),r!==null&&(r.prev=n),t!==null&&(t.first===e&&(t.first=r),t.last===e&&(t.last=n))}function rn(e,t,n=!0){var r=[];di(e,r,!0);var a=()=>{n&&he(e),t&&t()},o=r.length;if(o>0){var s=()=>--o||a();for(var l of r)l.out(s)}else a()}function di(e,t,n){if((e.f&Le)===0){e.f^=Le;var r=e.nodes&&e.nodes.t;if(r!==null)for(let l of r)(l.is_global||n)&&t.push(l);for(var a=e.first;a!==null;){var o=a.next;if((a.f&at)===0){var s=(a.f&ht)!==0||(a.f&Ze)!==0&&(e.f&Ve)!==0;di(a,t,s?n:!1)}a=o}}}function xs(e){hi(e,!0)}function hi(e,t){if((e.f&Le)!==0){e.f^=Le,(e.f&ue)===0&&(ne(e,de),vt.ensure().schedule(e));for(var n=e.first;n!==null;){var r=n.next,a=(n.f&ht)!==0||(n.f&Ze)!==0;hi(n,a?t:!1),n=r}var o=e.nodes&&e.nodes.t;if(o!==null)for(let s of o)(s.is_global||t)&&s.in()}}function vi(e,t){if(e.nodes)for(var n=e.nodes.start,r=e.nodes.end;n!==null;){var a=n===r?null:et(n);t.append(n),n=a}}function pa(e){let t={get:n=>Xt(t.store)[n],set:(n,r)=>{typeof n=="string"?Object.assign(Xt(t.store),{[n]:r}):Object.assign(Xt(t.store),n),t.store.set(Xt(t.store))},store:ts(e)};return t}globalThis.$altcha=globalThis.$altcha||{algorithms:new Map,defaults:pa({}),i18n:pa({}),instances:new Set,plugins:new Set};var Es={ariaLinkLabel:"Altcha (official website)",cancel:"Cancel",enterCode:"Enter code",enterCodeAria:"Enter code you hear. Press Space to play audio.",enterCodeFromImage:"To proceed, please enter the code from the image below.",error:"Verification failed. Try again later.",expired:"Verification expired. Try again.",footer:'Protected by <a href="https://altcha.org/" tabindex="-1" target="_blank" aria-label="Altcha (official website)">ALTCHA</a>',getAudioChallenge:"Get an audio challenge",label:"I'm not a robot",loading:"Loading...",reload:"Reload",verify:"Verify",verificationRequired:"Verification required!",verified:"Verified",verifying:"Verifying...",waitAlert:"Verifying... please wait."};globalThis.$altcha.i18n.set("en",Es);var Ss="5";typeof window<"u"&&((window.__svelte??={}).v??=new Set).add(Ss);var Qt=Symbol("events"),pi=new Set,mr=new Set;function gi(e,t,n,r={}){function a(o){if(r.capture||yr.call(t,o),!o.cancelBubble)return Ln(()=>n?.call(this,o))}return e.startsWith("pointer")||e.startsWith("touch")||e==="wheel"?rt(()=>{t.addEventListener(e,a,r)}):t.addEventListener(e,a,r),a}function ce(e,t,n,r,a){var o={capture:r,passive:a},s=gi(e,t,n,o);(t===document.body||t===window||t===document||t instanceof HTMLMediaElement)&&Mn(()=>{t.removeEventListener(e,s,o)})}function Nn(e,t,n){(t[Qt]??={})[e]=n}function Un(e){for(var t=0;t<e.length;t++)pi.add(e[t]);for(var n of mr)n(e)}var ga=null;function yr(e){var t=this,n=t.ownerDocument,r=e.type,a=e.composedPath?.()||[],o=a[0]||e.target;ga=e;var s=0,l=ga===e&&e[Qt];if(l){var u=a.indexOf(l);if(u!==-1&&(t===document||t===window)){e[Qt]=t;return}var c=a.indexOf(t);if(c===-1)return;u<=c&&(s=u)}if(o=a[s]||e.target,o!==t){an(e,"currentTarget",{configurable:!0,get(){return o||n}});var d=U,v=A;De(null),Qe(null);try{for(var h,b=[];o!==null;){var g=o.assignedSlot||o.parentNode||o.host||null;try{var E=o[Qt]?.[r];E!=null&&(!o.disabled||e.target===o)&&E.call(o,e)}catch($){h?b.push($):h=$}if(e.cancelBubble||g===t||g===null)break;o=g}if(h){for(let $ of b)queueMicrotask(()=>{throw $});throw h}}finally{e[Qt]=t,delete e.currentTarget,De(d),Qe(v)}}}var Cs=globalThis?.window?.trustedTypes&&globalThis.window.trustedTypes.createPolicy("svelte-trusted-html",{createHTML:e=>e});function Ts(e){return Cs?.createHTML(e)??e}function bi(e){var t=Rr("template");return t.innerHTML=Ts(e.replaceAll("<!>","<!---->")),t.content}function $e(e,t){var n=A;n.nodes===null&&(n.nodes={start:e,end:t,a:null,t:null})}function Z(e,t){var n=(t&jo)!==0,r=(t&Ho)!==0,a,o=!e.startsWith("<!>");return()=>{if(R)return $e(D,null),D;a===void 0&&(a=bi(o?e:"<!>"+e),n||(a=Te(a)));var s=r||Da?document.importNode(a,!0):a.cloneNode(!0);if(n){var l=Te(s),u=s.lastChild;$e(l,u)}else $e(s,s);return s}}function $s(e,t,n="svg"){var r=!e.startsWith("<!>"),a=`<${n}>${r?e:"<!>"+e}</${n}>`,o;return()=>{if(R)return $e(D,null),D;if(!o){var s=bi(a),l=Te(s);o=Te(l)}var u=o.cloneNode(!0);return $e(u,u),u}}function Vr(e,t){return $s(e,t,"svg")}function wn(e=""){if(!R){var t=He(e+"");return $e(t,t),t}var n=D;return n.nodeType!==ln?(n.before(n=He()),me(n)):Pn(n),$e(n,n),n}function ba(){if(R)return $e(D,null),D;var e=document.createDocumentFragment(),t=document.createComment(""),n=He();return e.append(t,n),$e(t,n),e}function M(e,t){if(R){var n=A;((n.f&gt)===0||n.nodes.end===null)&&(n.nodes.end=D),At();return}e!==null&&e.before(t)}function As(e){return e.endsWith("capture")&&e!=="gotpointercapture"&&e!=="lostpointercapture"}var Rs=["beforeinput","click","change","dblclick","contextmenu","focusin","focusout","input","keydown","keyup","mousedown","mousemove","mouseout","mouseover","mouseup","pointerdown","pointermove","pointerout","pointerover","pointerup","touchend","touchmove","touchstart"];function Is(e){return Rs.includes(e)}var Os={formnovalidate:"formNoValidate",ismap:"isMap",nomodule:"noModule",playsinline:"playsInline",readonly:"readOnly",defaultvalue:"defaultValue",defaultchecked:"defaultChecked",srcobject:"srcObject",novalidate:"noValidate",allowfullscreen:"allowFullscreen",disablepictureinpicture:"disablePictureInPicture",disableremoteplayback:"disableRemotePlayback"};function Ps(e){return e=e.toLowerCase(),Os[e]??e}var Ls=["touchstart","touchmove"];function Ms(e){return Ls.includes(e)}function We(e,t){var n=t==null?"":typeof t=="object"?`${t}`:t;n!==(e[fr]??=e.nodeValue)&&(e[fr]=n,e.nodeValue=`${n}`)}function mi(e,t){return yi(e,t)}function Ds(e,t){hr(),t.intro=t.intro??!1;let n=t.target,r=R,a=D;try{for(var o=Te(n);o&&(o.nodeType!==cn||o.data!==Tr);)o=et(o);if(!o)throw $t;Je(!0),me(o);let s=yi(e,{...t,anchor:o});return Je(!1),s}catch(s){if(s instanceof Error&&s.message.split(`
`).some(l=>l.startsWith("https://svelte.dev/e/")))throw s;return s!==$t&&console.warn("Failed to hydrate: ",s),t.recover===!1&&No(),hr(),Xo(n),Je(!1),mi(e,t)}finally{Je(r),me(a)}}var _n=new Map;function yi(e,{target:t,anchor:n,props:r={},events:a,context:o,intro:s=!0,transformError:l}){hr();var u=void 0,c=ws(()=>{var d=n??t.appendChild(He());ss(d,{pending:()=>{}},b=>{ot({});var g=ke;if(o&&(g.c=o),a&&(r.$$events=a),R&&$e(b,null),u=e(b,r)||{},R&&(A.nodes.end=D,D===null||D.nodeType!==cn||D.data!==Ia))throw un(),$t;st()},l);var v=new Set,h=b=>{for(var g=0;g<b.length;g++){var E=b[g];if(!v.has(E)){v.add(E);var $=Ms(E);for(let re of[t,document]){var P=_n.get(re);P===void 0&&(P=new Map,_n.set(re,P));var oe=P.get(E);oe===void 0?(re.addEventListener(E,yr,{passive:$}),P.set(E,1)):P.set(E,oe+1)}}}};return h(_o(pi)),mr.add(h),()=>{for(var b of v)for(let $ of[t,document]){var g=_n.get($),E=g.get(b);--E==0?($.removeEventListener(b,yr),g.delete(b),g.size===0&&_n.delete($)):g.set(b,E)}mr.delete(h),d!==n&&d.parentNode?.removeChild(d)}});return wr.set(u,c),u}var wr=new WeakMap;function Ns(e,t){let n=wr.get(e);return n?(wr.delete(e),n(t)):Promise.resolve()}var Ht=class{anchor;#e=new Map;#t=new Map;#n=new Map;#l=new Set;#r=!0;constructor(t,n=!0){this.anchor=t,this.#r=n}#i=t=>{if(this.#e.has(t)){var n=this.#e.get(t),r=this.#t.get(n);if(r)xs(r),this.#l.delete(n);else{var a=this.#n.get(n);a&&(this.#t.set(n,a.effect),this.#n.delete(n),a.fragment.lastChild.remove(),this.anchor.before(a.fragment),r=a.effect)}for(let[o,s]of this.#e){if(this.#e.delete(o),o===t)break;let l=this.#n.get(s);l&&(he(l.effect),this.#n.delete(s))}for(let[o,s]of this.#t){if(o===n||this.#l.has(o))continue;let l=()=>{if(Array.from(this.#e.values()).includes(o)){var c=document.createDocumentFragment();vi(s,c),c.append(He()),this.#n.set(o,{effect:s,fragment:c})}else he(s);this.#l.delete(o),this.#t.delete(o)};this.#r||!r?(this.#l.add(o),rn(s,l,!1)):l()}}};#a=t=>{this.#e.delete(t);let n=Array.from(this.#e.values());for(let[r,a]of this.#n)n.includes(r)||(he(a.effect),this.#n.delete(r))};ensure(t,n){var r=I,a=Qo();if(n&&!this.#t.has(t)&&!this.#n.has(t))if(a){var o=document.createDocumentFragment(),s=He();o.append(s),this.#n.set(t,{effect:Ue(()=>n(s)),fragment:o})}else this.#t.set(t,Ue(()=>n(this.anchor)));if(this.#e.set(r,t),a){for(let[l,u]of this.#t)l===t?r.unskip_effect(u):r.skip_effect(u);for(let[l,u]of this.#n)l===t?r.unskip_effect(u.effect):r.skip_effect(u.effect);r.oncommit(this.#i),r.ondiscard(this.#a)}else R&&(this.anchor=D),this.#i(r)}};function Us(e,t,...n){var r=new Ht(e);vn(()=>{let a=t()??null;r.ensure(a,a&&(o=>a(o,...n)))},ht)}function Fr(e){ke===null&&Io(),Ce(()=>{let t=hn(e);if(typeof t=="function")return t})}function le(e,t,n=!1){var r;R&&(r=D,At());var a=new Ht(e),o=n?ht:0;function s(l,u){if(R){var c=Ma(r);if(l!==parseInt(c.substring(1))){var d=Ar();me(d),a.anchor=d,Je(!1),a.ensure(l,u),Je(!0);return}}a.ensure(l,u)}vn(()=>{var l=!1;t((u,c=0)=>{l=!0,s(c,u)}),l||s(-1,null)},o)}var Vs=Symbol("NaN");function Fs(e,t,n){R&&At();var r=new Ht(e);vn(()=>{var a=t();a!==a&&(a=Vs),r.ensure(a,n)})}function wi(e,t,n=!1,r=!1,a=!1,o=!1){var s=e,l="";if(n){var u=e;R&&(s=me(Te(u)))}pe(()=>{var c=A;if(l===(l=t()??"")){R&&At();return}if(n&&!R){c.nodes=null,u.innerHTML=l,l!==""&&$e(Te(u),u.lastChild);return}if(c.nodes!==null&&(ui(c.nodes.start,c.nodes.end),c.nodes=null),l!==""){if(R){D.data;for(var d=At(),v=d;d!==null&&(d.nodeType!==cn||d.data!=="");)v=d,d=et(d);if(d===null)throw un(),$t;$e(D,v),s=me(d);return}var h=r?Bo:a?Ko:void 0,b=Rr(r?"svg":a?"math":"template",h);b.innerHTML=l;var g=r||a?b:b.content;if($e(Te(g),g.lastChild),r||a)for(;Te(g);)s.before(Te(g));else s.before(g)}})}function zs(e,t,n){var r;R&&(r=D,At());var a=new Ht(e);vn(()=>{var o=t()??null;if(R){var s=Ma(r),l=s===Tr,u=o!==null;if(l!==u){var c=Ar();me(c),a.anchor=c,Je(!1),a.ensure(o,o&&(d=>n(d,o))),Je(!0);return}}a.ensure(o,o&&(d=>n(d,o)))},ht)}function js(e,t){var n=void 0,r;li(()=>{n!==(n=t())&&(r&&(he(r),r=null),n&&(r=Ue(()=>{Nr(()=>n(e))})))})}function _i(e){var t,n,r="";if(typeof e=="string"||typeof e=="number")r+=e;else if(typeof e=="object")if(Array.isArray(e)){var a=e.length;for(t=0;t<a;t++)e[t]&&(n=_i(e[t]))&&(r&&(r+=" "),r+=n)}else for(n in e)e[n]&&(r&&(r+=" "),r+=n);return r}function Hs(){for(var e,t,n=0,r="",a=arguments.length;n<a;n++)(e=arguments[n])&&(t=_i(e))&&(r&&(r+=" "),r+=t);return r}function Bs(e){return typeof e=="object"?Hs(e):e??""}var ma=[...` 	
\r\f\xA0\v\uFEFF`];function Ks(e,t,n){var r=e==null?"":""+e;if(n){for(var a of Object.keys(n))if(n[a])r=r?r+" "+a:a;else if(r.length)for(var o=a.length,s=0;(s=r.indexOf(a,s))>=0;){var l=s+o;(s===0||ma.includes(r[s-1]))&&(l===r.length||ma.includes(r[l]))?r=(s===0?"":r.substring(0,s))+r.substring(l+1):s=l}}return r===""?null:r}function ya(e,t=!1){var n=t?" !important;":";",r="";for(var a of Object.keys(e)){var o=e[a];o!=null&&o!==""&&(r+=" "+a+": "+o+n)}return r}function nr(e){return e[0]!=="-"||e[1]!=="-"?e.toLowerCase():e}function Ys(e,t){if(t){var n="",r,a;if(Array.isArray(t)?(r=t[0],a=t[1]):r=t,e){e=String(e).replaceAll(/\s*\/\*.*?\*\/\s*/g,"").trim();var o=!1,s=0,l=!1,u=[];r&&u.push(...Object.keys(r).map(nr)),a&&u.push(...Object.keys(a).map(nr));var c=0,d=-1;let E=e.length;for(var v=0;v<E;v++){var h=e[v];if(l?h==="/"&&e[v-1]==="*"&&(l=!1):o?o===h&&(o=!1):h==="/"&&e[v+1]==="*"?l=!0:h==='"'||h==="'"?o=h:h==="("?s++:h===")"&&s--,!l&&o===!1&&s===0){if(h===":"&&d===-1)d=v;else if(h===";"||v===E-1){if(d!==-1){var b=nr(e.substring(c,d).trim());if(!u.includes(b)){h!==";"&&v++;var g=e.substring(c,v).trim();n+=" "+g+";"}}c=v+1,d=-1}}}}return r&&(n+=ya(r)),a&&(n+=ya(a,!0)),n=n.trim(),n===""?null:n}return e==null?null:String(e)}function Gs(e,t,n,r,a,o){var s=e[cr];if(R||s!==n||s===void 0){var l=Ks(n,r,o);(!R||l!==e.getAttribute("class"))&&(l==null?e.removeAttribute("class"):t?e.className=l:e.setAttribute("class",l)),e[cr]=n}else if(o&&a!==o)for(var u in o){var c=!!o[u];(a==null||c!==!!a[u])&&e.classList.toggle(u,c)}return o}function rr(e,t={},n,r){for(var a in n){var o=n[a];t[a]!==o&&(n[a]==null?e.style.removeProperty(a):e.style.setProperty(a,o,r))}}function qs(e,t,n,r){var a=e[ur];if(R||a!==t){var o=Ys(t,r);(!R||o!==e.getAttribute("style"))&&(o==null?e.removeAttribute("style"):e.style.cssText=o),e[ur]=t}else r&&(Array.isArray(r)?(rr(e,n?.[0],r[0]),rr(e,n?.[1],r[1],"important")):rr(e,n,r));return r}function _r(e,t,n=!1){if(e.multiple){if(t==null)return;if(!Ea(t))return Wo();for(var r of e.options)r.selected=t.includes(wa(r));return}for(r of e.options){var a=wa(r);if(Zo(a,t)){r.selected=!0;return}}(!n||t!==void 0)&&(e.selectedIndex=-1)}function Ws(e){var t=new MutationObserver(()=>{_r(e,e.__value)});t.observe(e,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["value"]}),Mn(()=>{t.disconnect()})}function wa(e){return"__value"in e?e.__value:e.value}var Jt=Symbol("class"),Zt=Symbol("style"),ki=Symbol("is custom element"),xi=Symbol("is html"),Js=sn?"link":"LINK",Zs=sn?"input":"INPUT",Xs=sn?"option":"OPTION",Qs=sn?"select":"SELECT",el=sn?"progress":"PROGRESS";function zr(e){if(R){var t=!1,n=()=>{if(!t){if(t=!0,e.hasAttribute("value")){var r=e.value;j(e,"value",null),e.value=r}if(e.hasAttribute("checked")){var a=e.checked;j(e,"checked",null),e.checked=a}}};e[en]=n,rt(n),Qa()}}function tl(e,t){var n=jr(e);n.value===(n.value=t??void 0)||e.value===t&&(t!==0||e.nodeName!==el)||(e.value=t??"")}function nl(e,t){t?e.hasAttribute("selected")||e.setAttribute("selected",""):e.removeAttribute("selected")}function j(e,t,n,r){var a=jr(e);R&&(a[t]=e.getAttribute(t),t==="src"||t==="srcset"||t==="href"&&e.nodeName===Js)||a[t]!==(a[t]=n)&&(t==="loading"&&(e[Ao]=n),n==null?e.removeAttribute(t):typeof n!="string"&&Ei(e).includes(t)?e[t]=n:e.setAttribute(t,n))}function rl(e,t,n,r,a=!1,o=!1){if(R&&a&&e.nodeName===Zs){var s=e,l=s.type==="checkbox"?"defaultChecked":"defaultValue";l in n||zr(s)}var u=jr(e),c=u[ki],d=!u[xi];let v=R&&c;v&&Je(!1);var h=t||{},b=e.nodeName===Xs;for(var g in t)g in n||(n[g]=null);n.class?n.class=Bs(n.class):n[Jt]&&(n.class=null),n[Zt]&&(n.style??=null);var E=Ei(e);for(let x in n){let L=n[x];if(b&&x==="value"&&L==null){e.value=e.__value="",h[x]=L;continue}if(x==="class"){var $=e.namespaceURI==="http://www.w3.org/1999/xhtml";Gs(e,$,L,r,t?.[Jt],n[Jt]),h[x]=L,h[Jt]=n[Jt];continue}if(x==="style"){qs(e,L,t?.[Zt],n[Zt]),h[x]=L,h[Zt]=n[Zt];continue}var P=h[x];if(!(L===P&&!(L===void 0&&e.hasAttribute(x)))){h[x]=L;var oe=x[0]+x[1];if(oe!=="$$")if(oe==="on"){let ee={},V="$$"+x,N=x.slice(2);var re=Is(N);if(As(N)&&(N=N.slice(0,-7),ee.capture=!0),!re&&P){if(L!=null)continue;e.removeEventListener(N,h[V],ee),h[V]=null}if(re)Nn(N,e,L),Un([N]);else if(L!=null){let Ne=function(ge){h[x].call(this,ge)};var se=Ne;h[V]=gi(N,e,Ne,ee)}}else if(x==="style")j(e,x,L);else if(x==="autofocus")hs(e,!!L);else if(!c&&(x==="__value"||x==="value"&&L!=null))e.value=e.__value=L;else if(x==="selected"&&b)nl(e,L);else{var H=x;d||(H=Ps(H));var Ke=H==="defaultValue"||H==="defaultChecked";if(L==null&&!c&&!Ke)if(u[x]=null,H==="value"||H==="checked"){let ee=e,V=t===void 0;if(H==="value"){let N=ee.defaultValue;ee.removeAttribute(H),ee.defaultValue=N,ee.value=ee.__value=V?N:null}else{let N=ee.defaultChecked;ee.removeAttribute(H),ee.defaultChecked=N,ee.checked=V?N:!1}}else e.removeAttribute(x);else Ke||E.includes(H)&&(c||typeof L!="string")?(e[H]=L,H in u&&(u[H]=ie)):typeof L!="function"&&j(e,H,L)}}}return v&&Je(!0),h}function Vn(e,t,n=[],r=[],a=[],o,s=!1,l=!1){Ya(a,n,r,u=>{var c=void 0,d={},v=e.nodeName===Qs,h=!1;if(li(()=>{var g=t(...u.map(i)),E=rl(e,c,g,o,s,l);h&&v&&"value"in g&&_r(e,g.value);for(let P of Object.getOwnPropertySymbols(d))g[P]||he(d[P]);for(let P of Object.getOwnPropertySymbols(g)){var $=g[P];P.description===Yo&&(!c||$!==c[P])&&(d[P]&&he(d[P]),d[P]=Ue(()=>js(e,()=>$))),E[P]=$}c=E}),v){var b=e;Nr(()=>{_r(b,c.value,!0),Ws(b)})}h=!0})}function jr(e){return e[Ta]??={[ki]:e.nodeName.includes("-"),[xi]:e.namespaceURI===Oa}}var _a=new Map;function Ei(e){var t=e.getAttribute("is")||e.nodeName,n=_a.get(t);if(n)return n;_a.set(t,n=[]);for(var r,a=e,o=Element.prototype;o!==a;){r=ko(a);for(var s in r)r[s].set&&s!=="innerHTML"&&s!=="textContent"&&s!=="innerText"&&n.push(s);a=Sa(a)}return n}function al(e,t,n=t){var r=new WeakSet;vs(e,"input",async a=>{var o=a?e.defaultValue:e.value;if(o=ar(e)?ir(o):o,n(o),I!==null&&r.add(I),await xt(),o!==(o=t())){var s=e.selectionStart,l=e.selectionEnd,u=e.value.length;if(e.value=o??"",l!==null){var c=e.value.length;s===l&&l===u&&c>u?(e.selectionStart=c,e.selectionEnd=c):(e.selectionStart=s,e.selectionEnd=Math.min(l,c))}}}),(R&&e.defaultValue!==e.value||hn(t)==null&&e.value)&&(n(ar(e)?ir(e.value):e.value),I!==null&&r.add(I)),Dn(()=>{var a=t();if(e===document.activeElement){var o=I;if(r.has(o))return}ar(e)&&a===ir(e.value)||e.type==="date"&&!a&&!e.value||a!==e.value&&(e.value=a??"")})}function ar(e){var t=e.type;return t==="number"||t==="range"}function ir(e){return e===""?null:+e}function or(e,t){return e===t||e?.[Ut]===t}function pt(e={},t,n,r){var a=ke.r,o=A;return Nr(()=>{var s,l;return Dn(()=>{s=l,l=[],hn(()=>{or(n(...l),e)||(t(e,...l),s&&or(n(...s),e)&&t(null,...s))})}),()=>{let u=o;for(;u!==a&&u.parent!==null&&u.parent.f&lr;)u=u.parent;let c=()=>{l&&or(n(...l),e)&&t(null,...l)},d=u.teardown;u.teardown=()=>{c(),d?.()}}}),e}var il={get(e,t){if(!e.exclude.includes(t))return e.props[t]},set(e,t){return!1},getOwnPropertyDescriptor(e,t){if(!e.exclude.includes(t)&&t in e.props)return{enumerable:!0,configurable:!0,value:e.props[t]}},has(e,t){return e.exclude.includes(t)?!1:t in e.props},ownKeys(e){return Reflect.ownKeys(e.props).filter(t=>!e.exclude.includes(t))}};function Fn(e,t,n){return new Proxy({props:e,exclude:t},il)}function J(e,t,n,r){var a=r,o=!0,s=()=>(o&&(o=!1,a=r),a),l;l=e[t],l===void 0&&r!==void 0&&(l=s());var u;u=()=>{var h=e[t];return h===void 0?s():(o=!0,h)};var c=!1,d=Lr(()=>(c=!1,u())),v=A;return(function(h,b){if(arguments.length>0){let g=b?i(d):h;return _(d,g),c=!0,a!==void 0&&(a=g),h}return it&&c||(v.f&je)!==0?d.v:i(d)})}function ol(e){return new kr(e)}var kr=class{#e;#t;constructor(t){var n=new Map,r=(o,s)=>{var l=Za(s,!1,!1);return n.set(o,l),l};let a=new Proxy({...t.props||{},$$events:{}},{get(o,s){return i(n.get(s)??r(s,Reflect.get(o,s)))},has(o,s){return s===$o?!0:(i(n.get(s)??r(s,Reflect.get(o,s))),Reflect.has(o,s))},set(o,s,l){return _(n.get(s)??r(s,l),l),Reflect.set(o,s,l)}});this.#t=(t.hydrate?Ds:mi)(t.component,{target:t.target,anchor:t.anchor,props:a,context:t.context,intro:t.intro??!1,recover:t.recover,transformError:t.transformError}),(!t?.props?.$$host||t.sync===!1)&&Y(),this.#e=a.$$events;for(let o of Object.keys(this.#t))o==="$set"||o==="$destroy"||o==="$on"||an(this,o,{get(){return this.#t[o]},set(s){this.#t[o]=s},enumerable:!0});this.#t.$set=o=>{Object.assign(a,o)},this.#t.$destroy=()=>{Ns(this.#t)}}$set(t){this.#t.$set(t)}$on(t,n){this.#e[t]=this.#e[t]||[];let r=(...a)=>n.call(this,...a);return this.#e[t].push(r),()=>{this.#e[t]=this.#e[t].filter(a=>a!==r)}}$destroy(){this.#t.$destroy()}},Si=class{};typeof HTMLElement=="function"&&(Si=class extends HTMLElement{$$ctor;$$s;$$c;$$cn=!1;$$d={};$$r=!1;$$p_d={};$$l={};$$l_u=new Map;$$me;$$shadowRoot=null;constructor(e,t,n){super(),this.$$ctor=e,this.$$s=t,n&&(this.$$shadowRoot=this.attachShadow(n))}addEventListener(e,t,n){if(this.$$l[e]=this.$$l[e]||[],this.$$l[e].push(t),this.$$c){let r=this.$$c.$on(e,t);this.$$l_u.set(t,r)}super.addEventListener(e,t,n)}removeEventListener(e,t,n){if(super.removeEventListener(e,t,n),this.$$c){let r=this.$$l_u.get(t);r&&(r(),this.$$l_u.delete(t))}}async connectedCallback(){if(this.$$cn=!0,!this.$$c){let t=function(a){return o=>{let s=Rr("slot");a!=="default"&&(s.name=a),M(o,s)}};var e=t;if(await Promise.resolve(),!this.$$cn||this.$$c)return;let n={},r=sl(this);for(let a of this.$$s)a in r&&(a==="default"&&!this.$$d.children?(this.$$d.children=t(a),n.default=!0):n[a]=t(a));for(let a of this.attributes){let o=this.$$g_p(a.name);o in this.$$d||(this.$$d[o]=En(o,a.value,this.$$p_d,"toProp"))}for(let a in this.$$p_d)!(a in this.$$d)&&this[a]!==void 0&&(this.$$d[a]=this[a],delete this[a]);this.$$c=ol({component:this.$$ctor,target:this.$$shadowRoot||this,props:{...this.$$d,$$slots:n,$$host:this}}),this.$$me=ys(()=>{Dn(()=>{this.$$r=!0;for(let a of Sn(this.$$c)){if(!this.$$p_d[a]?.reflect)continue;this.$$d[a]=this.$$c[a];let o=En(a,this.$$d[a],this.$$p_d,"toAttribute");o==null?this.removeAttribute(this.$$p_d[a].attribute||a):this.setAttribute(this.$$p_d[a].attribute||a,o)}this.$$r=!1})});for(let a in this.$$l)for(let o of this.$$l[a]){let s=this.$$c.$on(a,o);this.$$l_u.set(o,s)}this.$$l={}}}attributeChangedCallback(e,t,n){this.$$r||(e=this.$$g_p(e),this.$$d[e]=En(e,n,this.$$p_d,"toProp"),this.$$c?.$set({[e]:this.$$d[e]}))}disconnectedCallback(){this.$$cn=!1,Promise.resolve().then(()=>{!this.$$cn&&this.$$c&&(this.$$c.$destroy(),this.$$me(),this.$$c=void 0)})}$$g_p(e){return Sn(this.$$p_d).find(t=>this.$$p_d[t].attribute===e||!this.$$p_d[t].attribute&&t.toLowerCase()===e)||e}});function En(e,t,n,r){let a=n[e]?.type;if(t=a==="Boolean"&&typeof t!="boolean"?t!=null:t,!r||!n[e])return t;if(r==="toAttribute")switch(a){case"Object":case"Array":return t==null?null:JSON.stringify(t);case"Boolean":return t?"":null;case"Number":return t??null;default:return t}else switch(a){case"Object":case"Array":return t&&JSON.parse(t);case"Boolean":return t;case"Number":return t!=null?+t:t;default:return t}}function sl(e){let t={};return e.childNodes.forEach(n=>{t[n.slot||"default"]=!0}),t}function bt(e,t,n,r,a,o){let s=class extends Si{constructor(){super(e,n,a),this.$$p_d=t}static get observedAttributes(){return Sn(t).map(l=>(t[l].attribute||l).toLowerCase())}};return Sn(t).forEach(l=>{an(s.prototype,l,{get(){return this.$$c&&l in this.$$c?this.$$c[l]:this.$$d[l]},set(u){u=En(l,u,t),this.$$d[l]=u;var c=this.$$c;if(c){var d=Dt(c,l)?.get;d?c[l]=u:c.$set({[l]:u})}}})}),r.forEach(l=>{an(s.prototype,l,{get(){return this.$$c?.[l]}})}),e.element=s,s}var ll=Z('<div class="altcha-checkbox"><input/> <svg aria-hidden="true" width="12" height="9" viewBox="0 0 12 9"><polyline points="1 5 4 8 11 1"></polyline></svg> <div class="altcha-spinner altcha-checkbox-spinner" aria-hidden="true"></div></div>');function Ci(e,t){ot(t,!0);let n=J(t,"loading"),r=Fn(t,["$$slots","$$events","$$legacy","$$host","loading"]),a;function o(){a?.click()}var s={get loading(){return n()},set loading(d){n(d),Y()}},l=ll(),u=Q(l);Vn(u,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),pt(u,d=>a=d,()=>a);var c=W(u,2);return $r(2),q(l),pe(()=>j(l,"data-loading",n())),Nn("click",c,o),M(e,l),st(s)}Un(["click"]);bt(Ci,{loading:{}},[],[],{mode:"open"});var cl=Z('<div class="altcha-checkbox-native"><input/> <div class="altcha-spinner altcha-checkbox-native-spinner"></div></div>');function Ti(e,t){ot(t,!0);let n=J(t,"loading"),r=Fn(t,["$$slots","$$events","$$legacy","$$host","loading"]);var a={get loading(){return n()},set loading(l){n(l),Y()}},o=cl(),s=Q(o);return Vn(s,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),$r(2),q(o),pe(()=>j(o,"data-loading",n())),M(e,o),st(a)}bt(Ti,{loading:{}},[],[],{mode:"open"});var ul=Z('<div><a target="_blank" class="altcha-logo" aria-hidden="true" tabindex="-1"><svg width="22" height="22" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.33955 16.4279C5.88954 20.6586 12.1971 21.2105 16.4279 17.6604C18.4699 15.947 19.6548 13.5911 19.9352 11.1365L17.9886 10.4279C17.8738 12.5624 16.909 14.6459 15.1423 16.1284C11.7577 18.9684 6.71167 18.5269 3.87164 15.1423C1.03163 11.7577 1.4731 6.71166 4.8577 3.87164C8.24231 1.03162 13.2883 1.4731 16.1284 4.8577C16.9767 5.86872 17.5322 7.02798 17.804 8.2324L19.9522 9.01429C19.7622 7.07737 19.0059 5.17558 17.6604 3.57212C14.1104 -0.658624 7.80283 -1.21043 3.57212 2.33956C-0.658625 5.88958 -1.21046 12.1971 2.33955 16.4279Z" fill="currentColor"></path><path d="M3.57212 2.33956C1.65755 3.94607 0.496389 6.11731 0.12782 8.40523L2.04639 9.13961C2.26047 7.15832 3.21057 5.25375 4.8577 3.87164C8.24231 1.03162 13.2883 1.4731 16.1284 4.8577L13.8302 6.78606L19.9633 9.13364C19.7929 7.15555 19.0335 5.20847 17.6604 3.57212C14.1104 -0.658624 7.80283 -1.21043 3.57212 2.33956Z" fill="currentColor"></path><path d="M7 10H5C5 12.7614 7.23858 15 10 15C12.7614 15 15 12.7614 15 10H13C13 11.6569 11.6569 13 10 13C8.3431 13 7 11.6569 7 10Z" fill="currentColor"></path></svg></a></div>');function Hr(e,t){ot(t,!0);let n=J(t,"strings"),r="https://altcha.org";var a={get strings(){return n()},set strings(l){n(l),Y()}},o=ul(),s=Q(o);return j(s,"href",r),q(o),pe(()=>j(s,"aria-label",n().ariaLinkLabel)),M(e,o),st(a)}bt(Hr,{strings:{}},[],[],{mode:"open"});var fl=Z('<div class="altcha-footer"><p></p> <!></div>');function xr(e,t){ot(t,!0);let n=J(t,"logo"),r=J(t,"strings");var a={get logo(){return n()},set logo(c){n(c),Y()},get strings(){return r()},set strings(c){r(c),Y()}},o=fl(),s=Q(o);wi(s,()=>r().footer,!0),q(s);var l=W(s,2);{var u=c=>{Hr(c,{get strings(){return r()}})};le(l,c=>{n()&&c(u)})}return q(o),M(e,o),st(a)}bt(xr,{logo:{},strings:{}},[],[],{mode:"open"});var dl=Z('<div class="altcha-switch"><input/>  <div class="altcha-switch-toggle"><div class="altcha-spinner altcha-switch-spinner"></div></div></div>');function $i(e,t){ot(t,!0);let n=J(t,"loading"),r=Fn(t,["$$slots","$$events","$$legacy","$$host","loading"]),a;function o(){a?.click()}var s={get loading(){return n()},set loading(d){n(d),Y()}},l=dl(),u=Q(l);Vn(u,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),pt(u,d=>a=d,()=>a);var c=W(u,2);return q(l),pe(()=>j(l,"data-loading",n())),Nn("click",c,o),M(e,l),st(s)}Un(["click"]);bt($i,{loading:{}},[],[],{mode:"open"});var fe=(e=>(e.ERROR="error",e.LOADING="loading",e.PLAYING="playing",e.PAUSED="paused",e.READY="ready",e))(fe||{}),F=(e=>(e.CODE="code",e.ERROR="error",e.VERIFIED="verified",e.VERIFYING="verifying",e.UNVERIFIED="unverified",e.EXPIRED="expired",e))(F||{}),hl=Z('<div class="altcha-code-challenge-title"> </div>'),vl=Z('<div class="altcha-spinner"></div>'),pl=Vr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.8659 3.00017L22.3922 19.5002C22.6684 19.9785 22.5045 20.5901 22.0262 20.8662C21.8742 20.954 21.7017 21.0002 21.5262 21.0002H2.47363C1.92135 21.0002 1.47363 20.5525 1.47363 20.0002C1.47363 19.8246 1.51984 19.6522 1.60761 19.5002L11.1339 3.00017C11.41 2.52187 12.0216 2.358 12.4999 2.63414C12.6519 2.72191 12.7782 2.84815 12.8659 3.00017ZM10.9999 16.0002V18.0002H12.9999V16.0002H10.9999ZM10.9999 9.00017V14.0002H12.9999V9.00017H10.9999Z"></path></svg>'),gl=Vr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M15 7C15 6.44772 15.4477 6 16 6C16.5523 6 17 6.44772 17 7V17C17 17.5523 16.5523 18 16 18C15.4477 18 15 17.5523 15 17V7ZM7 7C7 6.44772 7.44772 6 8 6C8.55228 6 9 6.44772 9 7V17C9 17.5523 8.55228 18 8 18C7.44772 18 7 17.5523 7 17V7Z"></path></svg>'),bl=Vr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 12H7C8.10457 12 9 12.8954 9 14V19C9 20.1046 8.10457 21 7 21H4C2.89543 21 2 20.1046 2 19V12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12V19C22 20.1046 21.1046 21 20 21H17C15.8954 21 15 20.1046 15 19V14C15 12.8954 15.8954 12 17 12H20C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12Z"></path></svg>'),ml=Z('<button type="button" class="altcha-button altcha-button-secondary"><!></button>'),yl=Z('<audio hidden="" autoplay=""></audio>'),wl=Z('<div class="altcha-code-challenge"><form data-code-challenge="true"><!> <div class="altcha-code-challenge-text"> </div> <img class="altcha-code-challenge-image" alt=""/> <div class="altcha-code-challenge-row"><input type="text" class="altcha-input" autocomplete="off" name="" required=""/> <!> <button type="button" class="altcha-button altcha-button-secondary"><svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2V4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 9.25022 5.38734 6.82447 7.50024 5.38451L7.5 8H9.5V2L3.5 2V4L5.99918 3.99989C3.57075 5.82434 2 8.72873 2 12Z"></path></svg></button></div> <div class="altcha-code-challenge-buttons"><button type="submit" class="altcha-button"> </button> <button type="button" class="altcha-button altcha-button-secondary"> </button></div></form> <!></div>');function Ai(e,t){ot(t,!0);let n=J(t,"audioUrl"),r=J(t,"codeChallenge"),a=J(t,"config"),o=J(t,"imageUrl"),s=J(t,"onCancel"),l=J(t,"onReload"),u=J(t,"onSubmit"),c=J(t,"strings"),d=O(void 0),v=O(void 0),h=O(void 0),b=O(!1),g=O(""),E=O(!1);Fr(()=>(a().disableAutoFocus||xt().then(()=>{i(h)?.focus()}),()=>{i(v)&&(i(v).pause(),_(v,void 0))}));function $(){_(d,fe.PAUSED,!0)}function P(y){_(d,fe.ERROR,!0)}function oe(){_(d,fe.READY,!0)}function re(){_(d,fe.LOADING,!0)}function se(){_(d,fe.PLAYING,!0)}function H(){_(d,fe.PAUSED,!0)}function Ke(y){y.code==="Space"?(y.preventDefault(),y.stopPropagation(),L()):y.code==="Escape"&&(y.preventDefault(),y.stopPropagation(),s()?.())}function x(y){y.preventDefault(),y.stopPropagation(),u()?.(i(g))}function L(){i(v)?i(d)===fe.LOADING||(i(v).paused?(n()&&i(v).src!==n()&&(i(v).src=n()),i(v).currentTime=0,i(v).play()):i(v).pause()):(_(E,!0),requestAnimationFrame(()=>{i(v)&&n()&&(i(v).src=n(),i(v).play())}))}var ee={get audioUrl(){return n()},set audioUrl(y){n(y),Y()},get codeChallenge(){return r()},set codeChallenge(y){r(y),Y()},get config(){return a()},set config(y){a(y),Y()},get imageUrl(){return o()},set imageUrl(y){o(y),Y()},get onCancel(){return s()},set onCancel(y){s(y),Y()},get onReload(){return l()},set onReload(y){l(y),Y()},get onSubmit(){return u()},set onSubmit(y){u(y),Y()},get strings(){return c()},set strings(y){c(y),Y()}},V=wl(),N=Q(V),Ne=Q(N);{var ge=y=>{var te=hl(),wt=Q(te,!0);q(te),pe(()=>We(wt,c().verificationRequired)),M(y,te)};le(Ne,y=>{a().codeChallengeDisplay!=="standard"&&y(ge)})}var ye=W(Ne,2),X=Q(ye,!0);q(ye);var Ye=W(ye,2),S=W(Ye,2),B=Q(S);zr(B),B.disabled=i(b),pt(B,y=>_(h,y),()=>i(h));var xe=W(B,2);{var m=y=>{var te=ml(),wt=Q(te);{var Bn=we=>{var Ge=vl();M(we,Ge)},qt=we=>{var Ge=pl();M(we,Ge)},Kn=we=>{var Ge=gl();M(we,Ge)},Yn=we=>{var Ge=bl();M(we,Ge)};le(wt,we=>{i(d)===fe.LOADING?we(Bn):i(d)===fe.ERROR?we(qt,1):i(d)===fe.PLAYING?we(Kn,2):we(Yn,-1)})}q(te),pe(()=>{j(te,"title",c().getAudioChallenge),te.disabled=i(d)===fe.LOADING||i(d)===fe.ERROR,j(te,"aria-label",i(d)===fe.LOADING?c().loading:c().getAudioChallenge)}),ce("click",te,()=>L(),!0),M(y,te)};le(xe,y=>{r().audio&&y(m)})}var mt=W(xe,2);q(S);var pn=W(S,2),Ae=Q(pn),Hn=Q(Ae,!0);q(Ae);var yt=W(Ae,2),Kt=Q(yt,!0);q(yt),q(pn),q(N);var Yt=W(N,2);{var Gt=y=>{var te=yl();pt(te,wt=>_(v,wt),()=>i(v)),ce("error",te,P),ce("loadstart",te,re),ce("canplay",te,oe),ce("pause",te,H),ce("playing",te,se),ce("ended",te,$),M(y,te)};le(Yt,y=>{i(E)&&y(Gt)})}return q(V),pe(()=>{We(X,c().enterCodeFromImage),j(Ye,"src",o()),j(B,"minlength",r().length||1),j(B,"maxlength",r().length),j(B,"placeholder",c().enterCode),j(B,"aria-label",i(d)===fe.LOADING?c().loading:i(d)===fe.PLAYING?"":c().enterCodeAria),j(B,"aria-live",i(d)?"assertive":"polite"),j(B,"aria-busy",i(d)===fe.LOADING),j(mt,"title",c().reload),j(mt,"aria-label",c().reload),j(Ae,"aria-label",c().verify),We(Hn,c().verify),j(yt,"aria-label",c().cancel),We(Kt,c().cancel)}),ce("submit",N,x,!0),Nn("keydown",B,Ke),al(B,()=>i(g),y=>_(g,y)),ce("click",mt,()=>l()?.(),!0),ce("click",yt,()=>s()?.(),!0),M(e,V),st(ee)}Un(["keydown"]);bt(Ai,{audioUrl:{},codeChallenge:{},config:{},imageUrl:{},onCancel:{},onReload:{},onSubmit:{},strings:{}},[],[],{mode:"open"});var _l=Z('<div class="altcha-popover-backdrop" data-backdrop=""></div>'),kl=Z('<div class="altcha-popover-arrow"></div>'),xl=Z('<div role="button" class="altcha-popover-close">&times;</div>'),El=Z('<!> <div><!> <!> <div class="altcha-popover-content"><!></div></div>',1);function Er(e,t){ot(t,!0);let n=J(t,"anchor"),r=J(t,"children"),a=J(t,"display",7,"standard"),o=J(t,"backdrop",7,!1),s=J(t,"onClickOutside"),l=J(t,"onClickOutsideDelay",7,600),u=J(t,"onClose"),c=J(t,"placement",7,"auto"),d=J(t,"updateUISignal"),v=J(t,"variant",7,"neutral"),h=Fn(t,["$$slots","$$events","$$legacy","$$host","anchor","children","display","backdrop","onClickOutside","onClickOutsideDelay","onClose","placement","updateUISignal","variant"]),b=O(void 0),g=O(void 0),E=O(!1),$=O(0);Ce(()=>{c()!=="auto"&&_(E,c()==="top")}),Ce(()=>{d()&&H()}),Fr(()=>{let S=a()==="bottomsheet"||a()==="overlay";return S&&(i(g)&&document.body.append(i(g)),i(b)&&document.body.append(i(b))),H(),xt().then(()=>{_($,Date.now(),!0)}),()=>{S&&(i(g)&&document.body.removeChild(i(g)),i(b)&&document.body.removeChild(i(b)))}});function P(){u()?.()}function oe(S){let B=S.target;!i(b)?.contains(B)&&(!l()||i($)+l()<Date.now())&&s()?.()}function re(){H()}function se(){H()}function H(){if(n()&&c()==="auto"&&i(b)){let S=n().getBoundingClientRect(),xe=document.documentElement.clientHeight-(S.top+S.height)<i(b).clientHeight;i(E)!==xe&&_(E,xe)}}var Ke={get anchor(){return n()},set anchor(S){n(S),Y()},get children(){return r()},set children(S){r(S),Y()},get display(){return a()},set display(S="standard"){a(S),Y()},get backdrop(){return o()},set backdrop(S=!1){o(S),Y()},get onClickOutside(){return s()},set onClickOutside(S){s(S),Y()},get onClickOutsideDelay(){return l()},set onClickOutsideDelay(S=600){l(S),Y()},get onClose(){return u()},set onClose(S){u(S),Y()},get placement(){return c()},set placement(S="auto"){c(S),Y()},get updateUISignal(){return d()},set updateUISignal(S){d(S),Y()},get variant(){return v()},set variant(S="neutral"){v(S),Y()}},x=El();ce("click",Et,oe,!0),ce("resize",Et,re),ce("scroll",Et,se);var L=Lt(x);{var ee=S=>{var B=_l();pt(B,xe=>_(g,xe),()=>i(g)),M(S,B)};le(L,S=>{o()&&S(ee)})}var V=W(L,2);Vn(V,()=>({...h,class:`altcha-popover ${(t.class||"")??""}`,"data-popover":!0,"data-variant":v(),"data-top":i(E),"data-display":a()}));var N=Q(V);{var Ne=S=>{var B=kl();M(S,B)};le(N,S=>{a()==="standard"&&S(Ne)})}var ge=W(N,2);{var ye=S=>{var B=xl();ce("click",B,P,!0),M(S,B)};le(ge,S=>{a()!=="standard"&&S(ye)})}var X=W(ge,2),Ye=Q(X);return Us(Ye,()=>r()??ft),q(X),q(V),pt(V,S=>_(b,S),()=>i(b)),M(e,x),st(Ke)}bt(Er,{anchor:{},children:{},display:{},backdrop:{},onClickOutside:{},onClickOutsideDelay:{},onClose:{},placement:{},updateUISignal:{},variant:{}},[],[],{mode:"open"});function Sl(e){return Array.from(new Uint8Array(e)).map(t=>t.toString(16).padStart(2,"0")).join("")}function Cl(e,t="altcha-css",n){if(typeof document<"u"&&document&&!document.getElementById(t)){let r=document.createElement("style");r.id=t,r.textContent=e;let a=document.currentScript?.nonce??document.querySelector('meta[name="csp-nonce"]')?.content;a&&(r.nonce=a),document.head.appendChild(r)}}async function Ri(e){let{challenge:t,concurrency:n=navigator.hardwareConcurrency,controller:r=new AbortController,createWorker:a,onOutOfMemory:o=h=>h>1?Math.floor(h/2):0,counterMode:s,timeout:l=9e4}=e,u=Math.min(16,Math.max(1,n)),c=[],d=()=>{for(let h of c)h.terminate()};for(let h=0;h<u;h++)c.push(await a(t.parameters.algorithm));let v=null;try{v=await Promise.race(c.map((h,b)=>(r.signal.addEventListener("abort",()=>{h.postMessage({type:"abort"})}),new Promise((g,E)=>{h.addEventListener("error",$=>{E($)}),h.addEventListener("message",$=>{if($.data){for(let P of c)P!==h&&P.postMessage({type:"abort"});if($.data.error)return E(new Error($.data.error))}g($.data)}),h.postMessage({challenge:t,counterMode:s,counterStart:b,counterStep:u,timeout:l,type:"work"})}))))}catch(h){if(h instanceof Error&&!!h?.message?.includes("Out of memory")&&o){d();let g=o(u);if(g)return Ri({...e,challenge:t,controller:r,concurrency:g,createWorker:a})}throw h}finally{d()}return r.signal.aborted?null:v||null}var Sr=class{TAG_CODES={INPUT:1,TEXTAREA:2,SELECT:3,BUTTON:4,A:5,DETAILS:6,SUMMARY:7,IFRAME:8,VIDEO:9,AUDIO:10};maxSamples;sampleInterval;target;focusStartTime=0;focusInteraction=0;focusInteractionTimer=null;lastPointerSample=0;lastTouchSample=0;lastScrollSample=0;pendingPointer=null;pendingTouch=null;focus=[];pointer=[];scroll=[];touch=[];constructor(t={}){let{maxSamples:n=60,sampleInterval:r=50,target:a=window}=t;this.maxSamples=n,this.sampleInterval=r,this.target=a,this.attach()}destroy(){let t={capture:!0};this.target.removeEventListener("focusin",this.onFocus,t),this.target.removeEventListener("keydown",this.onInteraction,t),this.target.removeEventListener("pointerdown",this.onInteraction,t),this.target.removeEventListener("pointermove",this.onPointer,t),this.target.removeEventListener("scroll",this.onScroll,t),this.target.removeEventListener("touchmove",this.onTouchMove,t)}export(){return{focus:this.focus,maxTouchPoints:navigator.maxTouchPoints||0,pointer:this.pointer,scroll:this.scroll,time:Date.now(),touch:this.touch}}attach(){let t={passive:!0,capture:!0};this.target.addEventListener("focusin",this.onFocus,t),this.target.addEventListener("keydown",this.onInteraction,t),this.target.addEventListener("pointerdown",this.onInteraction,t),this.target.addEventListener("pointermove",this.onPointer,t),this.target.addEventListener("scroll",this.onScroll,t),this.target.addEventListener("touchmove",this.onTouchMove,t)}evict(t){t.length>this.maxSamples&&t.splice(0,t.length-this.maxSamples)}onFocus=t=>{if(this.focusInteraction===2)return;let n=t.target;if(!(n instanceof Element))return;let r=performance.now();this.focusStartTime===0&&(this.focusStartTime=r),this.focus.push([Math.round(r-this.focusStartTime),n.tabIndex,this.TAG_CODES[n.tagName]??0,this.focusInteraction?1:0]),this.evict(this.focus)};onInteraction=t=>{this.focusInteraction="keyCode"in t?1:2,this.focusInteractionTimer&&clearTimeout(this.focusInteractionTimer),this.focusInteractionTimer=setTimeout(()=>{this.focusInteraction=0},100)};onPointer=t=>{if(t.pointerType==="touch")return;let n=t.timeStamp||performance.now();this.pendingPointer=[Math.round(t.clientX),Math.round(t.clientY),Math.round(n)],n-this.lastPointerSample>=this.sampleInterval&&(this.pointer.push(this.pendingPointer),this.lastPointerSample=n,this.pendingPointer=null,this.evict(this.pointer))};onScroll=()=>{let t=performance.now();t-this.lastScrollSample<this.sampleInterval||(this.scroll.push([Math.round(window.scrollY),Math.round(t)]),this.lastScrollSample=t,this.evict(this.scroll))};onTouchMove=t=>{let n=t.timeStamp||performance.now(),r=t.touches[0];r&&(this.pendingTouch=[Math.round(r.clientX),Math.round(r.clientY),Math.round(n),Math.round(r.force*1e3)/1e3,Math.round(r.radiusX||0),Math.round(r.radiusY||0)],n-this.lastTouchSample>=this.sampleInterval&&(this.touch.push(this.pendingTouch),this.lastTouchSample=n,this.pendingTouch=null,this.evict(this.touch)))}},Tl=Z('<div class="altcha-overlay-backdrop" data-backdrop=""></div>'),$l=Z('<div class="altcha-overlay-content"></div>'),Al=Z('<div role="button" class="altcha-overlay-close">&times;</div> <!>',1),Rl=Z('<div class="altcha-floating-arrow"></div>'),Il=Z('<input type="hidden"/>'),Ol=Z('<div class="altcha-error">Secure context (HTTPS) required.</div>'),Pl=Z('<div class="altcha-error"> </div>'),Ll=Z('<div class="altcha-error"> </div>'),Ml=Z("<!> <!>",1),Dl=Z('<!> <div class="altcha"><!> <div class="altcha-main"><div><div class="altcha-checkbox-wrap"><!> <label><!></label></div> <!></div> <!> <!> <!></div> <!></div>',1);function Nl(e,t){ot(t,!0);let n=()=>ca(d,"$altchaDefaults",a),r=()=>ca(g,"$altchaI18nStore",a),[a,o]=ns(),s='input[type="text"]:not([data-no-spamfilter]), textarea:not([data-no-spamfilter])',l='input[type="submit"], button[type="submit"], button:not([type="button"]):not([type="reset"])',u=["ar","fa","he","ur"],{isSecureContext:c}=globalThis,{store:d}=globalThis.$altcha.defaults,v=navigator.hardwareConcurrency||2,h=navigator.deviceMemory||0,b=h&&h<=4?Math.min(4,v):v,g=globalThis.$altcha.i18n.store,E=t.$$host,$=(f,p)=>{xt().then(()=>{E?.dispatchEvent(new CustomEvent(f,{detail:p}))})},P=null,oe=O(nt(new URL(location.origin))),re=O(!1),se=O(null),H=O(null),Ke=O(null),x=O(nt(F.UNVERIFIED)),L=O(void 0),ee=O(void 0),V=O(null),N=O(void 0),Ne=O(null),ge=O(null),ye=O(null),X=O(null),Ye=O(nt([])),S=O(0),B=O(nt({})),xe=O(!0),m=be(()=>({fetch:(f,p)=>fetch(f,p),audioChallengeLanguage:"",auto:"off",barPlacement:"bottom",challenge:"",codeChallenge:null,codeChallengeDisplay:"standard",credentials:null,debug:!1,disableAutoFocus:!1,display:"standard",floatingAnchor:"",floatingOffset:8,floatingPersist:!1,floatingPlacement:"auto",hideFooter:!1,hideLogo:!1,humanInteractionSignature:!0,language:"",mockError:!1,minDuration:500,overlayContent:"",name:"altcha",popoverPlacement:"auto",retryOnOutOfMemoryError:!0,setCookie:null,serverVerificationFields:!1,serverVerificationTimeZone:!1,test:!1,timeout:9e4,type:"checkbox",validationMessage:"",verifyFunction:null,verifyUrl:"",workers:b,...n(),...i(B)})),mt=be(()=>`altcha-checkbox-${t.id||Math.floor(Math.random()*1e12).toString(16)}`),pn=be(()=>Di(i(m).type)),Ae=be(()=>i(m).auto),Hn=be(()=>i(x)===F.VERIFYING),yt=be(()=>!i(m).hideFooter),Kt=be(()=>!i(m).hideLogo&&i(m).display!=="bar"),Yt=be(()=>Ni(r(),[i(m).language,document.documentElement.lang,...navigator.languages])),Gt=be(()=>u.includes(i(Yt).language)?"rtl":void 0),y=be(()=>({...i(Yt).strings})),te=be(()=>i(se)?.audio?.match(/^(https?:)?\//)?gn(i(se).audio,i(oe),{language:i(m).audioChallengeLanguage||i(Yt).language}).toString():i(se)?.audio),wt=be(()=>i(se)?.image?.match(/^(https?:)?\//)?gn(i(se).image,i(oe)):i(se)?.image);Ce(()=>{Wt({auto:t.auto,challenge:t.challenge,display:t.display,language:t.language,name:t.name,type:t.type,workers:t.workers})}),Ce(()=>{if(t.configuration)try{Wt(JSON.parse(t.configuration))}catch{K("unable to parse the `configuration` attribute (JSON expected)")}}),Ce(()=>{i(Ke)!==i(m).display&&bn(i(m).display)}),Ce(()=>{i(re)&&i(x)===F.VERIFYING&&_(re,!1)}),Ce(()=>{!i(re)&&i(x)===F.VERIFIED&&_(re,!0)}),Ce(()=>{if(!i(re)){let f=Gn();f&&f.checked&&(f.checked=!1)}}),Ce(()=>{i(x)===F.VERIFIED&&Gn()?.setCustomValidity("")}),Ce(()=>{if(i(Ae)==="onload"){let f=setTimeout(()=>{It()},1);return()=>{f&&clearTimeout(f)}}}),Ce(()=>{i(ge)&&K("error:",i(ge))}),Ce(()=>{i(X)&&i(m).setCookie&&Zi(i(X),i(m).setCookie)}),Fr(()=>(K("mounted","3.2.1"),E&&globalThis.$altcha.instances.add(E),_(V,i(N)?.closest("form"),!0),i(V)?.addEventListener("reset",Wr),i(V)?.addEventListener("submit",Jr,{capture:!0}),i(V)?.addEventListener("focusin",qr),Bn(),i(m).humanInteractionSignature&&(K("human interaction signature enabled"),P=new Sr),$("load"),c||K("secure context (HTTPS) required"),()=>{Kn(),E&&globalThis.$altcha.instances.delete(E),i(ye)&&clearTimeout(i(ye)),i(V)?.removeEventListener("reset",Wr),i(V)?.removeEventListener("submit",Jr,{capture:!0}),i(V)?.removeEventListener("focusin",qr),P?.destroy()}));function Bn(){_(Ye,[...globalThis.$altcha.plugins].map(f=>new f(E)),!0),K("activating plugins",i(Ye).map(f=>f.constructor.name));for(let f of i(Ye))f.activate()}async function qt(f,...p){let w;for(let k of i(Ye))w=await k[f].call(k,...p);return w}function Kn(){for(let f of i(Ye))f.destroy()}function Yn(f){let[p,w]=f.salt.split("?"),k={};if(w)try{Object.assign(k,Object.fromEntries(new URLSearchParams(w).entries()))}catch{}let T={codeChallenge:f.codeChallenge,parameters:{algorithm:f.algorithm,cost:1,data:k,expiresAt:k?.expires?parseInt(k.expires,10):void 0,keyLength:f.algorithm==="SHA-512"?64:f.algorithm==="SHA-384"?48:32,nonce:Sl(new TextEncoder().encode(f.salt)),keyPrefix:f.challenge,salt:""},signature:f.signature};return Object.defineProperties(T,{_originalSalt:{enumerable:!1,value:f.salt,writable:!1},_version:{enumerable:!1,value:1,writable:!1}}),T}function we(f,p){return{algorithm:f.parameters.algorithm,challenge:f.parameters.keyPrefix,number:p.counter,salt:"_originalSalt"in f?f._originalSalt:f.parameters.nonce,signature:f.signature,took:p.time||0}}async function Ge(f){await new Promise(p=>setTimeout(p,f))}async function Gr(f=i(m).challenge,p){let w=await qt("onFetchChallenge",f),k=null;if(w!==void 0)return w;if(typeof f=="string")if(f.startsWith("{")){K("parsing JSON challenge");try{k=JSON.parse(f)}catch{throw new Error("Unable to parse JSON challenge.")}}else{K("fetching challenge from",p?.method||"GET",f),_(oe,new URL(f,location.origin),!0);let T=await i(m).fetch(f,{credentials:i(m).credentials||void 0,...p});await Xr(T);let C=T.headers.get("x-altcha-config");C&&qi(C);let z=await T.json();if(z&&"his"in z&&z.his){if(K("requested HIS"),!P)throw new Error("Server requested HIS data but collector is disabled.");return Gr(gn(z.his.url,i(oe)),{body:JSON.stringify({his:P.export()}),headers:{"content-type":"application/json"},method:"POST"})}z&&"hisResult"in z&&z.hisResult&&K("HIS result",z.hisResult),k=z}else if(f&&typeof f=="object")try{k=JSON.parse(JSON.stringify(f))}catch{throw new Error("Unable to parse JSON challenge.")}if(Li(k)&&(k=Yn(k)),!Mi(k))throw new Error("Challenge validation failed.");return k}function Li(f){return typeof f=="object"&&"challenge"in f}function Mi(f){return!!f&&typeof f=="object"&&"parameters"in f&&!!f.parameters&&typeof f.parameters=="object"&&"algorithm"in f.parameters&&"nonce"in f.parameters&&"salt"in f.parameters&&"keyPrefix"in f.parameters}function Gn(){return document.getElementById(i(mt))}function Di(f){switch(f){case"checkbox":return Ci;case"switch":return $i;default:return Ti}}function Ni(f,p){let w=Object.keys(f).map(T=>T.toLowerCase()),k=p.reduce((T,C)=>(C=C.toLowerCase(),T||(f[C]?C:null)||w.find(z=>C.split("-")[0]===z.split("-")[0])||null),null);return f[k||""]||(k="en"),{language:k,strings:f[k]}}function Ui(f){switch(f){case"bar":return i(m).barPlacement||"bottom";case"floating":return i(m).floatingPlacement||"auto";default:return}}function Vi(f){return[...i(V)?.querySelectorAll(s)||[]].reduce((w,k)=>{let T=k.name,C=k.value;return T&&C&&(w[T]=/\n/.test(C)?C.replace(new RegExp("(?<!\\r)\\n","g"),`\r
`):C),w},{})}function Fi(){try{return Intl.DateTimeFormat().resolvedOptions().timeZone}catch{}}function gn(f,p,w){let k=new URL(f,p);if(k.search||(k.search=p.search),w)for(let T in w)w[T]!==void 0&&w[T]!==null&&k.searchParams.set(T,w[T]);return k.toString()}function zi(f){!i(re)&&f.currentTarget.checked?(f.preventDefault(),f.currentTarget.checked=!1,i(x)!==F.VERIFYING&&It()):f.currentTarget.checked||(f.preventDefault(),Re())}function ji(f){i(x)===F.VERIFYING?f.currentTarget.setCustomValidity(i(y).waitAlert):i(m).validationMessage&&f.currentTarget.setCustomValidity(i(m).validationMessage)}function Hi(){bn(i(m).display),Re()}function Bi(){mn()}function Ki(f){let p=f.target;i(m).display==="floating"&&p&&!E?.contains(p)&&!p.hasAttribute("data-backdrop")&&!p.closest("[data-popover]")&&i(x)!==F.VERIFIED&&!i(m).floatingPersist&&qn()}function qr(f){i(Ae)==="onfocus"&&i(x)===F.UNVERIFIED&&It()}function Wr(){bn(i(m).display),Re()}function Jr(f){f.target?.getAttribute("data-code-challenge")!=="true"&&i(Ae)==="onsubmit"&&i(x)===F.UNVERIFIED&&(f.preventDefault(),f.stopPropagation(),_(Ne,f.submitter,!0),Wn(),It().then(w=>{w&&!i(se)&&xt().then(()=>{Zr(i(Ne))})}))}function Yi(f){f.persisted&&(bn(i(m).display),Re())}function Gi(){mn()}function qi(f){try{let p=JSON.parse(f);p&&typeof p=="object"&&Wt({serverVerificationFields:p?.sentinel?.fields,serverVerificationTimeZone:p?.sentinel?.timeZone,verifyUrl:p.verifyurl,...p})}catch(p){K("unable to configure from x-altcha-config header",p)}}function Wi(f=20){if(!i(N))return;let p=i(m).floatingPlacement;if(!i(ee)&&(_(ee,(i(m).floatingAnchor instanceof HTMLElement?i(m).floatingAnchor:i(m).floatingAnchor?document.querySelector(i(m).floatingAnchor):i(V)?.querySelector(l))||i(V),!0),!i(ee))){K("unable to find floating anchor element");return}let w=parseInt(i(m).floatingOffset,10)||12,k=i(ee).getBoundingClientRect(),T=i(N).getBoundingClientRect(),C=document.documentElement.clientHeight,z=document.documentElement.clientWidth,Ee=!p||p==="auto"?k.bottom+T.height+w+f>C:p==="top",G=Math.max(f,Math.min(z-f-T.width,k.left+k.width/2-T.width/2));if(i(N).style.setProperty("--altcha-floating-left",`${G}px`),i(N).style.setProperty("--altcha-floating-top",Ee?`${k.top-(T.height+w)}px`:`${k.bottom+w}px`),i(N).setAttribute("data-floating-position",Ee?"top":"bottom"),i(L)){let ae=i(L).getBoundingClientRect();i(L).style.left=k.left-G+k.width/2-ae.width/2+"px"}}async function Ji(f,p){let w=await qt("onRequestServerVerification",f,p);if(w!==void 0)return w;if(K("requesting server verification from",i(m).verifyUrl),!i(m).verifyUrl)throw new Error("Parameter verifyUrl must be set for server verification.");let k=await i(m).fetch(gn(i(m).verifyUrl,i(oe)),{body:JSON.stringify({code:p,fields:i(m).serverVerificationFields?Vi():void 0,payload:f,timeZone:i(m).serverVerificationTimeZone?Fi():void 0}),credentials:i(m).credentials||void 0,headers:{"Content-Type":"application/json"},method:"POST"});await Xr(k);let T=await k.json();return T&&typeof T=="object"&&"payload"in T&&T.payload&&$("serververification",T),T}function Zr(f){i(V)&&"requestSubmit"in i(V)?i(V).requestSubmit(f):i(V)?.reportValidity()&&(f?f.click():i(V).submit())}function Zi(f,p={}){let{domain:w,name:k=i(m).name,maxAge:T,path:C,sameSite:z,secure:Ee}=p,G=`${encodeURIComponent(k)}=${encodeURIComponent(f)}`;w&&(G+=`; Domain=${w}`),T!=null&&(G+=`; Max-Age=${T}`),C&&(G+=`; Path=${C}`),z&&(G+=`; SameSite=${z}`),Ee&&(G+="; Secure"),document.cookie=G}function bn(f){switch(f){case"bar":case"floating":case"overlay":qn(),(!i(Ae)||i(Ae)==="off")&&(i(B).auto="onsubmit");break;case"standard":Wn()}i(Ke)!==f&&_(Ke,f,!0)}function Xi(f){i(ye)&&clearTimeout(i(ye));let p=()=>{i(x)!==F.UNVERIFIED?(_(re,!1),Ie(F.EXPIRED)):Re(),$("expired")},w=f*1e3-Date.now();w>=1?_(ye,setTimeout(p,w),!0):p()}async function Xr(f){if(f.status>=400){if(f.headers.get("content-type")?.includes("/json")){let w;try{w=await f.json()}catch{}if(w&&"error"in w)throw new Error(`Server responded with ${f.status} - ${w.error}`)}throw new Error(`Server responded with ${f.status}.`)}let p=f.headers.get("content-type");if(!p||!p.includes("/json"))throw new Error(`Server responded with invalid content-type. Expected application/json, received ${p}.`)}async function Qr(f){if(!i(X)){Ie(F.ERROR,"Cannot verify code challenge without PoW payload.");return}Ie(F.VERIFYING);let p=null;if(i(m).verifyUrl)p=await Ji(i(X),f);else if(i(m).verifyFunction)p=await i(m).verifyFunction(i(X),f);else{Ie(F.ERROR,"Parameter verifyUrl is required for code challenge verification.");return}p?.payload&&(_(X,p.payload,!0),K("server payload",i(X))),p?.verified===!0?(K("verified"),Ie(F.VERIFIED),$("verified",{payload:i(X)}),i(Ae)==="onsubmit"&&xt().then(()=>{Zr(i(Ne))})):Ie(F.ERROR,p?.reason||"Verification failed."),i(m).disableAutoFocus||Gn()?.focus()}function Wt(f){Object.assign(i(B),{...Object.fromEntries(Object.entries(f).filter(([p,w])=>w!==void 0))})}function Qi(){return{...i(m)}}function eo(){return i(x)}function qn(){_(xe,!1)}function K(...f){(i(m).debug||f.some(p=>p instanceof Error))&&console[f[0]instanceof Error?"error":"log"]("ALTCHA",`[name=${i(m).name}]`,...f)}function Re(f=F.UNVERIFIED,p=null){_(re,!1),_(ge,p,!0),_(X,null),i(H)&&i(H).abort(),i(ye)&&(clearTimeout(i(ye)),_(ye,null)),Ie(f)}function Ie(f,p=null){_(x,f,!0),_(ge,p,!0),$("statechange",{payload:i(X),state:i(x)})}function Wn(){_(xe,!0),xt().then(()=>{mn()})}function mn(){if(i(m).display==="floating")return Wi();_(S,i(S)+1)}async function It(f={}){let{concurrency:p=Math.max(1,i(m).workers),controller:w=new AbortController,minDuration:k=i(m).minDuration}=f,T=performance.now(),C=null,z=null,Ee=!1,G=await qt("onVerify",f);if(G!==void 0)return G;Re(F.VERIFYING),_(H,w,!0);try{if(!c)throw new Error("Secure context (HTTPS) required.");if(i(m).mockError)throw new Error("Mock error.");if(i(m).test)return K("running test mode with null challenge"),await Ge(Math.max(0,k-(performance.now()-T))),i(H)?.signal.aborted?(Re(),null):(_(X,btoa(JSON.stringify({challenge:null,solution:null,test:!0})),!0),K("verified"),Ie(F.VERIFIED),$("verified",{payload:i(X)}),{payload:i(X)});if(C=await Gr(),!C)throw new Error("Failed to fetch challenge.");K("challenge",C),"configuration"in C&&(K("re-configuring from challenge",C.configuration),Wt(C.configuration)),C.parameters.expiresAt&&Xi(C.parameters.expiresAt),Ee="_version"in C&&C._version===1;let ae=globalThis.$altcha.algorithms.get(C.parameters.algorithm);if(!ae)throw new Error(`Unsupported algorithm ${C.parameters.algorithm}.`);if(z=await Ri({challenge:C,concurrency:p,controller:w,createWorker:ae,counterMode:Ee?"string":"uint32",onOutOfMemory:lt=>{if(K("out of memory error received"),$("outofmemory"),i(m).retryOnOutOfMemoryError&&lt>1){let ct=Math.floor(lt/2);return K(`retrying with ${ct} workers...`),ct}},timeout:i(m).timeout}),i(H)?.signal.aborted)return Re(),null;if(!z)throw new Error("Failed to find solution.");K("solution",z),await Ge(Math.max(0,k-(performance.now()-T))),_(se,C.codeChallenge||i(m).codeChallenge||null,!0),Ee?_(X,btoa(JSON.stringify(we(C,z))),!0):_(X,btoa(JSON.stringify({challenge:{parameters:C.parameters,signature:C.signature},solution:z})),!0),i(se)?(K("requesting code verification"),Ie(F.CODE),$("codechallenge",{codeChallenge:i(se)})):i(m).verifyUrl?await Qr():(K("verified"),Ie(F.VERIFIED),$("verified",{payload:i(X)}))}catch(ae){return K("verification failed",ae),Ie(F.ERROR,String(ae)),null}finally{_(H,null)}return{challenge:C,payload:i(X),solution:z}}var to={configure:Wt,getConfiguration:Qi,getState:eo,hide:qn,log:K,reset:Re,setState:Ie,show:Wn,updateUI:mn,verify:It},ea=Dl();ce("scroll",dr,Bi),ce("click",dr,Ki),ce("pageshow",Et,Yi),ce("resize",Et,Gi);var ta=Lt(ea);{var no=f=>{var p=Tl();M(f,p)};le(ta,f=>{i(m).display==="overlay"&&i(xe)&&f(no)})}var qe=W(ta,2),na=Q(qe);{var ro=f=>{var p=Al(),w=Lt(p),k=W(w,2);{var T=C=>{var z=$l();wi(z,()=>document.querySelector(i(m).overlayContent)?.innerHTML,!0),q(z),M(C,z)};le(k,C=>{i(m).overlayContent&&C(T)})}ce("click",w,Hi,!0),M(f,p)};le(na,f=>{i(m).display==="overlay"&&i(xe)&&f(ro)})}var Jn=W(na,2),Zn=Q(Jn),Xn=Q(Zn),ra=Q(Xn);{let f=be(()=>i(m).display==="standard"&&i(Ae)!=="onsubmit"||i(x)===F.VERIFYING);zs(ra,()=>i(pn),(p,w)=>{w(p,{get id(){return i(mt)},name:"",get required(){return i(f)},get loading(){return i(Hn)},get checked(){return i(re)},onchange:zi,oninvalid:ji})})}var Qn=W(ra,2),ao=Q(Qn);{var io=f=>{var p=wn();pe(()=>We(p,i(y).verificationRequired)),M(f,p)},oo=f=>{var p=wn();pe(()=>We(p,i(y).verifying)),M(f,p)},so=f=>{var p=wn();pe(()=>We(p,i(y).verified)),M(f,p)},lo=f=>{var p=wn();pe(()=>We(p,i(y).label)),M(f,p)};le(ao,f=>{i(x)===F.CODE&&i(se)?f(io):i(x)===F.VERIFYING?f(oo,1):i(x)===F.VERIFIED?f(so,2):f(lo,-1)})}q(Qn),q(Xn);var co=W(Xn,2);{var uo=f=>{Hr(f,{get strings(){return i(y)}})};le(co,f=>{i(Kt)&&f(uo)})}q(Zn);var aa=W(Zn,2);{var fo=f=>{{let p=be(()=>i(m).display==="bar"&&i(Kt));xr(f,{get logo(){return i(p)},get strings(){return i(y)}})}};le(aa,f=>{i(yt)&&f(fo)})}var ia=W(aa,2);{var ho=f=>{var p=Rl();pt(p,w=>_(L,w),()=>i(L)),M(f,p)};le(ia,f=>{i(m).display==="floating"&&f(ho)})}var vo=W(ia,2);{var po=f=>{var p=Il();zr(p),pe(()=>{j(p,"name",i(m).name),tl(p,i(X))}),M(f,p)};le(vo,f=>{i(m).setCookie||f(po)})}q(Jn);var go=W(Jn,2);{var bo=f=>{Er(f,{get anchor(){return i(N)},onClickOutside:()=>{c&&Re()},get placement(){return i(m).popoverPlacement},role:"alert",variant:"error",get dir(){return i(Gt)},get updateUISignal(){return i(S)},children:(p,w)=>{var k=ba(),T=Lt(k);{var C=G=>{var ae=Ol();M(G,ae)},z=G=>{var ae=Pl(),lt=Q(ae,!0);q(ae),pe(()=>We(lt,i(y).expired)),M(G,ae)},Ee=G=>{var ae=Ll(),lt=Q(ae,!0);q(ae),pe(()=>{j(ae,"title",i(ge)),We(lt,i(y).error)}),M(G,ae)};le(T,G=>{!i(ge)&&!c?G(C):!i(ge)&&i(x)===F.EXPIRED?G(z,1):G(Ee,-1)})}M(p,k)},$$slots:{default:!0}})},mo=f=>{var p=ba(),w=Lt(p);Fs(w,()=>i(se),k=>{{let T=be(()=>i(m).codeChallengeDisplay!=="standard");Er(k,{get anchor(){return i(N)},get backdrop(){return i(T)},get display(){return i(m).codeChallengeDisplay},onClose:()=>{Re()},get placement(){return i(m).popoverPlacement},role:"dialog",get"aria-label"(){return i(y).verificationRequired},get dir(){return i(Gt)},get updateUISignal(){return i(S)},children:(C,z)=>{var Ee=Ml(),G=Lt(Ee);Ai(G,{get audioUrl(){return i(te)},get imageUrl(){return i(wt)},onCancel:()=>Re(),onReload:()=>It(),onSubmit:ct=>Qr(ct),get codeChallenge(){return i(se)},get config(){return i(m)},get strings(){return i(y)}});var ae=W(G,2);{var lt=ct=>{xr(ct,{get logo(){return i(Kt)},get strings(){return i(y)}})};le(ae,ct=>{i(yt)&&i(m).codeChallengeDisplay!=="standard"&&ct(lt)})}M(C,Ee)},$$slots:{default:!0}})}}),M(f,p)};le(go,f=>{i(ge)||i(x)===F.EXPIRED||!c?f(bo):i(se)&&i(x)===F.CODE&&f(mo,1)})}q(qe),pt(qe,f=>_(N,f),()=>i(N)),pe(f=>{j(qe,"data-state",i(x)),j(qe,"data-display",i(m).display||void 0),j(qe,"data-placement",f),j(qe,"data-visible",i(xe)||void 0),j(qe,"dir",i(Gt)),j(Qn,"for",i(mt)),qe.dir=qe.dir},[()=>Ui(i(m).display)]),M(e,ea);var yo=st(to);return o(),yo}typeof window<"u"&&window.customElements&&!customElements.get("altcha-widget")&&customElements.define("altcha-widget",bt(Nl,{auto:{type:"String"},challenge:{type:"String"},configuration:{type:"String"},display:{type:"String"},language:{type:"String"},name:{type:"String"},theme:{type:"String"},type:{type:"String"},workers:{type:"Number"}},[],["configure","getConfiguration","getState","hide","log","reset","setState","show","updateUI","verify"]));var Ii=`(function() {
  "use strict";
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
    const buffer = new ArrayBuffer(hex.length / 2);
    const view = new DataView(buffer);
    for (let i = 0; i < hex.length; i += 2) {
      const byteString = hex.substring(i, i + 2);
      const byteValue = parseInt(byteString, 16);
      view.setUint8(i / 2, byteValue);
    }
    return new Uint8Array(buffer);
  }
  async function delay(ms) {
    await new Promise((resolve) => setTimeout(resolve, ms));
  }
  function timeDuration(start) {
    return Math.floor((performance.now() - start) * 10) / 10;
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
     */
    setCounter(n) {
      if (this.mode === "string") {
        return concatBuffers(this.nonce, this.encoder.encode(n.toString()));
      }
      this.dataView.setUint32(this.nonce.length, n, false);
      return this.buffer;
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
    const { nonce, keyPrefix, salt } = challenge.parameters;
    const nonceBuf = hexToBuffer(nonce);
    const saltBuf = hexToBuffer(salt);
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
      if (keyPrefixBuf ? bufferStartsWith(derivedKey, keyPrefixBuf) : bufferToHex(derivedKey).startsWith(keyPrefix)) {
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
  function getDigest(algorithm) {
    switch (algorithm) {
      case "PBKDF2/SHA-512":
        return "SHA-512";
      case "PBKDF2/SHA-384":
        return "SHA-384";
      case "PBKDF2/SHA-256":
      default:
        return "SHA-256";
    }
  }
  async function deriveKey(parameters, salt, password) {
    const { algorithm, cost, keyLength = 32 } = parameters;
    const passwordKey = await crypto.subtle.importKey(
      "raw",
      password,
      { name: "PBKDF2" },
      false,
      ["deriveKey"]
    );
    const derivedKey = await crypto.subtle.deriveKey(
      {
        name: "PBKDF2",
        salt,
        iterations: cost,
        hash: getDigest(algorithm)
      },
      passwordKey,
      { name: "AES-GCM", length: keyLength * 8 },
      true,
      ["encrypt"]
    );
    return {
      derivedKey: new Uint8Array(await crypto.subtle.exportKey("raw", derivedKey))
    };
  }
  handler({
    deriveKey
  });
})();
`,ka=typeof self<"u"&&self.Blob&&new Blob(["(self.URL || self.webkitURL).revokeObjectURL(self.location.href);",Ii],{type:"text/javascript;charset=utf-8"});function Br(e){let t;try{if(t=ka&&(self.URL||self.webkitURL).createObjectURL(ka),!t)throw"";let n=new Worker(t,{name:e?.name});return n.addEventListener("error",()=>{(self.URL||self.webkitURL).revokeObjectURL(t)}),n}catch{return new Worker("data:text/javascript;charset=utf-8,"+encodeURIComponent(Ii),{name:e?.name})}}var Oi=`(function() {
  "use strict";
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
    const buffer = new ArrayBuffer(hex.length / 2);
    const view = new DataView(buffer);
    for (let i = 0; i < hex.length; i += 2) {
      const byteString = hex.substring(i, i + 2);
      const byteValue = parseInt(byteString, 16);
      view.setUint8(i / 2, byteValue);
    }
    return new Uint8Array(buffer);
  }
  async function delay(ms) {
    await new Promise((resolve) => setTimeout(resolve, ms));
  }
  function timeDuration(start) {
    return Math.floor((performance.now() - start) * 10) / 10;
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
     */
    setCounter(n) {
      if (this.mode === "string") {
        return concatBuffers(this.nonce, this.encoder.encode(n.toString()));
      }
      this.dataView.setUint32(this.nonce.length, n, false);
      return this.buffer;
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
    const { nonce, keyPrefix, salt } = challenge.parameters;
    const nonceBuf = hexToBuffer(nonce);
    const saltBuf = hexToBuffer(salt);
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
      if (keyPrefixBuf ? bufferStartsWith(derivedKey, keyPrefixBuf) : bufferToHex(derivedKey).startsWith(keyPrefix)) {
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
    const iterations = Math.max(1, parameters.cost);
    let data = void 0;
    let derivedKey = void 0;
    for (let i = 0; i < iterations; i++) {
      if (i === 0) {
        data = concatBuffers(salt, password);
      } else {
        data = derivedKey;
      }
      derivedKey = new Uint8Array(
        (await crypto.subtle.digest(algorithm, data)).slice(0, keyLength)
      );
    }
    return {
      parameters: {},
      derivedKey
    };
  }
  handler({
    deriveKey
  });
})();
`,xa=typeof self<"u"&&self.Blob&&new Blob(["(self.URL || self.webkitURL).revokeObjectURL(self.location.href);",Oi],{type:"text/javascript;charset=utf-8"});function Kr(e){let t;try{if(t=xa&&(self.URL||self.webkitURL).createObjectURL(xa),!t)throw"";let n=new Worker(t,{name:e?.name});return n.addEventListener("error",()=>{(self.URL||self.webkitURL).revokeObjectURL(t)}),n}catch{return new Worker("data:text/javascript;charset=utf-8,"+encodeURIComponent(Oi),{name:e?.name})}}var Ul=`:root {
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
}`;Cl(Ul);$altcha.algorithms.set("SHA-256",()=>new Kr);$altcha.algorithms.set("SHA-384",()=>new Kr);$altcha.algorithms.set("SHA-512",()=>new Kr);$altcha.algorithms.set("PBKDF2/SHA-256",()=>new Br);$altcha.algorithms.set("PBKDF2/SHA-384",()=>new Br);$altcha.algorithms.set("PBKDF2/SHA-512",()=>new Br);var jn=()=>{};function Vl(e,t){return e!=e?t==t:e!==t||e!==null&&typeof e=="object"||typeof e=="function"}function Fl(e,t,n){if(e==null)return t(void 0),jn;let r=jl(()=>e.subscribe(t,n));return r.unsubscribe?()=>r.unsubscribe():r}var Bt=[];function zl(e,t=jn){let n=null,r=new Set;function a(l){if(Vl(e,l)&&(e=l,n)){let u=!Bt.length;for(let c of r)c[1](),Bt.push(c,e);if(u){for(let c=0;c<Bt.length;c+=2)Bt[c][0](Bt[c+1]);Bt.length=0}}}function o(l){a(l(e))}function s(l,u=jn){let c=[l,u];return r.add(c),r.size===1&&(n=t(a,o)||jn),l(e),()=>{r.delete(c),r.size===0&&n&&(n(),n=null)}}return{set:a,update:o,subscribe:s}}function zn(e){let t;return Fl(e,n=>t=n)(),t}var Yr=!1;function jl(e){var t=Yr;try{return Yr=!0,e()}finally{Yr=t}}function Pi(e){let t={get:n=>zn(t.store)[n],set:(n,r)=>{typeof n=="string"?Object.assign(zn(t.store),{[n]:r}):Object.assign(zn(t.store),n),t.store.set(zn(t.store))},store:zl(e)};return t}globalThis.$altcha=globalThis.$altcha||{algorithms:new Map,defaults:Pi({}),i18n:Pi({}),instances:new Set,plugins:new Set};var Hl={ariaLinkLabel:"Altcha (site officiel)",enterCode:"Entrez le code",enterCodeAria:"Entrez le code que vous entendez. Appuyez sur Espace pour \xE9couter l'audio.",error:"\xC9chec de la v\xE9rification. Essayez \xE0 nouveau plus tard.",expired:"La v\xE9rification a expir\xE9. Essayez \xE0 nouveau.",footer:'Prot\xE9g\xE9 par <a href="https://altcha.org/" tabindex="-1" target="_blank" aria-label="Altcha (site officiel)">ALTCHA</a>',getAudioChallenge:"Obtenir un d\xE9fi audio",label:"Je ne suis pas un robot",loading:"Chargement...",reload:"Recharger",verify:"V\xE9rifier",verificationRequired:"V\xE9rification requise !",verified:"V\xE9rifi\xE9",verifying:"V\xE9rification en cours...",waitAlert:"V\xE9rification en cours... veuillez patienter.",cancel:"Annuler",enterCodeFromImage:"Pour continuer, veuillez entrer le code de l'image ci-dessous."};globalThis.$altcha.i18n.set("fr-fr",Hl);})();
//# sourceMappingURL=altcha.js.map
