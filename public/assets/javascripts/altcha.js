(()=>{var wa=Array.isArray,po=Array.prototype.indexOf,Vt=Array.prototype.includes,go=Array.from,En=Object.keys,rn=Object.defineProperty,Mt=Object.getOwnPropertyDescriptor,bo=Object.getOwnPropertyDescriptors,mo=Object.prototype,yo=Array.prototype,_a=Object.getPrototypeOf,na=Object.isExtensible,ft=()=>{};function wo(e){for(var t=0;t<e.length;t++)e[t]()}function ka(){var e,t,n=new Promise((r,a)=>{e=r,t=a});return{promise:n,resolve:e,reject:t}}var ve=2,Ft=4,Rn=8,xr=1<<24,Ve=16,Ze=32,at=64,ar=128,Pe=512,ue=1024,de=2048,Xe=4096,Le=8192,je=16384,gt=32768,ir=1<<25,ht=65536,Cn=1<<17,_o=1<<18,Rt=1<<19,ko=1<<20,Tt=65536,Sn=1<<21,Nt=1<<22,dt=1<<23,Ut=Symbol("$state"),xo=Symbol("legacy props"),Eo=Symbol(""),xa=Symbol("attributes"),or=Symbol("class"),lr=Symbol("style"),sr=Symbol("text"),Qt=Symbol("form reset"),In=new class extends Error{name="StaleReactionError";message="The reaction that called `getAbortSignal()` was re-run or destroyed"},on=!!globalThis.document?.contentType&&globalThis.document.contentType.includes("xml"),ln=3,sn=8;function Ea(e){return e===this.v}function Ca(e,t){return e!=e?t==t:e!==t||e!==null&&typeof e=="object"||typeof e=="function"}function Co(e){return!Ca(e,this.v)}function So(e){throw new Error("https://svelte.dev/e/lifecycle_outside_component")}function To(){throw new Error("https://svelte.dev/e/async_derived_orphan")}function $o(e){throw new Error("https://svelte.dev/e/effect_in_teardown")}function Ao(){throw new Error("https://svelte.dev/e/effect_in_unowned_derived")}function Ro(e){throw new Error("https://svelte.dev/e/effect_orphan")}function Io(){throw new Error("https://svelte.dev/e/effect_update_depth_exceeded")}function Oo(){throw new Error("https://svelte.dev/e/hydration_failed")}function Po(){throw new Error("https://svelte.dev/e/state_descriptors_fixed")}function Lo(){throw new Error("https://svelte.dev/e/state_prototype_fixed")}function Do(){throw new Error("https://svelte.dev/e/state_unsafe_mutation")}function Mo(){throw new Error("https://svelte.dev/e/svelte_boundary_reset_onerror")}var No=1,Uo=2,Er="[",Sa="[!",ra="[?",Ta="]",$t={},ie=Symbol("uninitialized"),$a="http://www.w3.org/1999/xhtml",Vo="http://www.w3.org/2000/svg",Fo="http://www.w3.org/1998/Math/MathML",zo="@attach",ke=null;function zt(e){ke=e}function ot(e,t=!1,n){ke={p:ke,i:!1,c:null,e:null,s:e,x:null,r:A,l:null}}function lt(e){var t=ke,n=t.e;if(n!==null){t.e=null;for(var r of n)ri(r)}return e!==void 0&&(t.x=e),t.i=!0,ke=t.p,e??{}}function Aa(){return!0}var _t=[];function Ra(){var e=_t;_t=[],wo(e)}function rt(e){if(_t.length===0&&!en){var t=_t;queueMicrotask(()=>{t===_t&&Ra()})}_t.push(e)}function jo(){for(;_t.length>0;)Ra()}function Ho(){console.warn("https://svelte.dev/e/derived_inert")}function cn(e){console.warn("https://svelte.dev/e/hydration_mismatch")}function Bo(){console.warn("https://svelte.dev/e/select_multiple_invalid_value")}function Ko(){console.warn("https://svelte.dev/e/svelte_boundary_reset_noop")}var R=!1;function Je(e){R=e}var M;function me(e){if(e===null)throw cn(),$t;return M=e}function At(){return me(et(M))}function q(e){if(R){if(et(M)!==null)throw cn(),$t;M=e}}function Cr(e=1){if(R){for(var t=e,n=M;t--;)n=et(n);M=n}}function Sr(e=!0){for(var t=0,n=M;;){if(n.nodeType===sn){var r=n.data;if(r===Ta){if(t===0)return n;t-=1}else(r===Er||r===Sa||r[0]==="["&&!isNaN(Number(r.slice(1))))&&(t+=1)}var a=et(n);e&&n.remove(),n=a}}function Ia(e){if(!e||e.nodeType!==sn)throw cn(),$t;return e.data}function nt(e){if(typeof e!="object"||e===null||Ut in e)return e;let t=_a(e);if(t!==mo&&t!==yo)return e;var n=new Map,r=wa(e),a=O(0),o=St,l=s=>{if(St===o)return s();var c=U,u=St;Me(null),ua(o);var d=s();return Me(c),ua(u),d};return r&&n.set("length",O(e.length)),new Proxy(e,{defineProperty(s,c,u){(!("value"in u)||u.configurable===!1||u.enumerable===!1||u.writable===!1)&&Po();var d=n.get(c);return d===void 0?l(()=>{var v=O(u.value);return n.set(c,v),v}):_(d,u.value,!0),!0},deleteProperty(s,c){var u=n.get(c);if(u===void 0){if(c in s){let d=l(()=>O(ie));n.set(c,d),tn(a)}}else _(u,ie),tn(a);return!0},get(s,c,u){if(c===Ut)return e;var d=n.get(c),v=c in s;if(d===void 0&&(!v||Mt(s,c)?.writable)&&(d=l(()=>{var b=nt(v?s[c]:ie),g=O(b);return g}),n.set(c,d)),d!==void 0){var h=i(d);return h===ie?void 0:h}return Reflect.get(s,c,u)},getOwnPropertyDescriptor(s,c){var u=Reflect.getOwnPropertyDescriptor(s,c);if(u&&"value"in u){var d=n.get(c);d&&(u.value=i(d))}else if(u===void 0){var v=n.get(c),h=v?.v;if(v!==void 0&&h!==ie)return{enumerable:!0,configurable:!0,value:h,writable:!0}}return u},has(s,c){if(c===Ut)return!0;var u=n.get(c),d=u!==void 0&&u.v!==ie||Reflect.has(s,c);if(u!==void 0||A!==null&&(!d||Mt(s,c)?.writable)){u===void 0&&(u=l(()=>{var h=d?nt(s[c]):ie,b=O(h);return b}),n.set(c,u));var v=i(u);if(v===ie)return!1}return d},set(s,c,u,d){var v=n.get(c),h=c in s;if(r&&c==="length")for(var b=u;b<v.v;b+=1){var g=n.get(b+"");g!==void 0?_(g,ie):b in s&&(g=l(()=>O(ie)),n.set(b+"",g))}if(v===void 0)(!h||Mt(s,c)?.writable)&&(v=l(()=>O(void 0)),_(v,nt(u)),n.set(c,v));else{h=v.v!==ie;var E=l(()=>nt(u));_(v,E)}var $=Reflect.getOwnPropertyDescriptor(s,c);if($?.set&&$.set.call(d,u),!h){if(r&&typeof c=="string"){var P=n.get("length"),oe=Number(c);Number.isInteger(oe)&&oe>=P.v&&_(P,oe+1)}tn(a)}return!0},ownKeys(s){i(a);var c=Reflect.ownKeys(s).filter(v=>{var h=n.get(v);return h===void 0||h.v!==ie});for(var[u,d]of n)d.v!==ie&&!(u in s)&&c.push(u);return c},setPrototypeOf(){Lo()}})}function aa(e){try{if(e!==null&&typeof e=="object"&&Ut in e)return e[Ut]}catch{}return e}function Yo(e,t){return Object.is(aa(e),aa(t))}var Et,cr,Oa,Pa,La;function ur(){if(Et===void 0){Et=window,cr=document,Oa=/Firefox/.test(navigator.userAgent);var e=Element.prototype,t=Node.prototype,n=Text.prototype;Pa=Mt(t,"firstChild").get,La=Mt(t,"nextSibling").get,na(e)&&(e[or]=void 0,e[xa]=null,e[lr]=void 0,e.__e=void 0),na(n)&&(n[sr]=void 0)}}function He(e=""){return document.createTextNode(e)}function Te(e){return Pa.call(e)}function et(e){return La.call(e)}function Q(e,t){if(!R)return Te(e);var n=Te(M);if(n===null)n=M.appendChild(He());else if(t&&n.nodeType!==ln){var r=He();return n?.before(r),me(r),r}return t&&On(n),me(n),n}function Lt(e,t=!1){if(!R){var n=Te(e);return n instanceof Comment&&n.data===""?et(n):n}if(t){if(M?.nodeType!==ln){var r=He();return M?.before(r),me(r),r}On(M)}return M}function W(e,t=1,n=!1){let r=R?M:e;for(var a;t--;)a=r,r=et(r);if(!R)return r;if(n){if(r?.nodeType!==ln){var o=He();return r===null?a?.after(o):r.before(o),me(o),o}On(r)}return me(r),r}function Go(e){e.textContent=""}function qo(){return!1}function Tr(e,t,n){return document.createElementNS(t??$a,e,void 0)}function On(e){if(e.nodeValue.length<65536)return;let t=e.nextSibling;for(;t!==null&&t.nodeType===ln;)t.remove(),e.nodeValue+=t.nodeValue,t=e.nextSibling}function Da(e){var t=A;if(t===null)return U.f|=dt,e;if((t.f&gt)===0&&(t.f&Ft)===0)throw e;ut(e,t)}function ut(e,t){for(;t!==null;){if((t.f&ar)!==0){if((t.f&gt)===0)throw e;try{t.b.error(e);return}catch(n){e=n}}t=t.parent}throw e}var Wo=-7169;function ne(e,t){e.f=e.f&Wo|t}function $r(e){(e.f&Pe)!==0||e.deps===null?ne(e,ue):ne(e,Xe)}function Ma(e){if(e!==null)for(let t of e)(t.f&ve)===0||(t.f&Tt)===0||(t.f^=Tt,Ma(t.deps))}function Na(e,t,n){(e.f&de)!==0?t.add(e):(e.f&Xe)!==0&&n.add(e),Ma(e.deps),ne(e,ue)}function Ua(e,t,n){if(e==null)return t(void 0),ft;let r=dn(()=>e.subscribe(t,n));return r.unsubscribe?()=>r.unsubscribe():r}var Ot=[];function Jo(e,t=ft){let n=null,r=new Set;function a(s){if(Ca(e,s)&&(e=s,n)){let c=!Ot.length;for(let u of r)u[1](),Ot.push(u,e);if(c){for(let u=0;u<Ot.length;u+=2)Ot[u][0](Ot[u+1]);Ot.length=0}}}function o(s){a(s(e))}function l(s,c=ft){let u=[s,c];return r.add(u),r.size===1&&(n=t(a,o)||ft),s(e),()=>{r.delete(u),r.size===0&&n&&(n(),n=null)}}return{set:a,update:o,subscribe:l}}function Zt(e){let t;return Ua(e,n=>t=n)(),t}var fr=Symbol("unmounted");function ia(e,t,n){let r=n[t]??={store:null,source:Ga(void 0),unsubscribe:ft};if(r.store!==e&&!(fr in n))if(r.unsubscribe(),r.store=e??null,e==null)r.source.v=void 0,r.unsubscribe=ft;else{var a=!0;r.unsubscribe=Ua(e,o=>{a?r.source.v=o:_(r.source,o)}),a=!1}return e&&fr in n?Zt(e):i(r.source)}function Zo(){let e={};function t(){Ln(()=>{for(var n in e)e[n].unsubscribe();rn(e,fr,{enumerable:!1,value:!0})})}return[e,t]}var Zn=null,Pt=null,I=null,dr=null,Fe=null,hr=null,en=!1,Xn=!1,Dt=null,_n=null,oa=0;var Xo=1,vt=class e{id=Xo++;#e=!1;linked=!0;#t=null;#n=null;async_deriveds=new Map;current=new Map;previous=new Map;unblocked=new Set;#s=new Set;#r=new Set;#i=new Set;#a=0;#o=new Map;#d=null;#l=[];#v=[];#h=new Set;#c=new Set;#u=new Map;#f=new Set;is_fork=!1;#b=!1;#_(){if(this.is_fork)return!0;for(let r of this.#o.keys()){for(var t=r,n=!1;t.parent!==null;){if(this.#u.has(t)){n=!0;break}t=t.parent}if(!n)return!0}return!1}skip_effect(t){this.#u.has(t)||this.#u.set(t,{d:[],m:[]}),this.#f.delete(t)}unskip_effect(t,n=r=>this.schedule(r)){var r=this.#u.get(t);if(r){this.#u.delete(t);for(var a of r.d)ne(a,de),n(a);for(a of r.m)ne(a,Xe),n(a)}this.#f.add(t)}#g(){if(this.#e=!0,oa++>1e3&&(this.#w(),Qo()),!this.#_()){for(let c of this.#h)this.#c.delete(c),ne(c,de),this.schedule(c);for(let c of this.#c)ne(c,Xe),this.schedule(c)}let t=this.#l;this.#l=[],this.apply();var n=Dt=[],r=[],a=_n=[];for(let c of t)try{this.#k(c,n,r)}catch(u){throw za(c),u}if(I=null,a.length>0){var o=e.ensure();for(let c of a)o.schedule(c)}if(Dt=null,_n=null,this.#_()){this.#p(r),this.#p(n);for(let[c,u]of this.#u)Fa(c,u);a.length>0&&I.#g();return}let l=this.#x();if(l){l.#m(this);return}this.#h.clear(),this.#c.clear();for(let c of this.#s)c(this);this.#s.clear(),dr=this,la(r),la(n),dr=null,this.#d?.resolve();var s=I;if(this.linked&&this.#a===0&&this.#w(),this.#l.length>0){s===null&&(s=this,this.#y());let c=s;c.#l.push(...this.#l.filter(u=>!c.#l.includes(u)))}s!==null&&s.#g()}#k(t,n,r){t.f^=ue;for(var a=t.first;a!==null;){var o=a.f,l=(o&(Ze|at))!==0,s=l&&(o&ue)!==0,c=s||(o&Le)!==0||this.#u.has(a);if(!c&&a.fn!==null){l?a.f^=ue:(o&Ft)!==0?n.push(a):fn(a)&&((o&Ve)!==0&&this.#c.add(a),jt(a));var u=a.first;if(u!==null){a=u;continue}}for(;a!==null;){var d=a.next;if(d!==null){a=d;break}a=a.parent}}}#x(){for(var t=this.#t;t!==null;){if(!t.is_fork){for(let[n,[,r]]of this.current)if(t.current.has(n)&&!r)return t}t=t.#t}return null}#m(t){for(let[r,a]of t.current)!this.previous.has(r)&&t.previous.has(r)&&this.previous.set(r,t.previous.get(r)),this.current.set(r,a);for(let[r,a]of t.async_deriveds){let o=this.async_deriveds.get(r);o&&a.promise.then(o.resolve)}let n=r=>{var a=r.reactions;if(a!==null)for(let s of a){var o=s.f;if((o&ve)!==0)n(s);else{var l=s;o&(Nt|Ve)&&!this.async_deriveds.has(l)&&(this.#c.delete(l),ne(l,de),this.schedule(l))}}};for(let r of this.current.keys())n(r);this.oncommit(()=>t.discard()),t.#w(),I=this,this.#g()}#p(t){for(var n=0;n<t.length;n+=1)Na(t[n],this.#h,this.#c)}capture(t,n,r=!1){t.v!==ie&&!this.previous.has(t)&&this.previous.set(t,t.v),(t.f&dt)===0&&(this.current.set(t,[n,r]),Fe?.set(t,n)),this.is_fork||(t.v=n)}activate(){I=this}deactivate(){I=null,Fe=null}flush(){try{Xn=!0,I=this,this.#g()}finally{oa=0,hr=null,Dt=null,_n=null,Xn=!1,I=null,Fe=null,Ct.clear()}}discard(){for(let t of this.#r)t(this);this.#r.clear(),this.#i.clear(),this.#w()}register_created_effect(t){this.#v.push(t)}#E(){this.#w();for(let d=Zn;d!==null;d=d.#n){var t=d.id<this.id,n=[];for(let[v,[h,b]]of this.current){if(d.current.has(v)){var r=d.current.get(v)[0];if(t&&h!==r)d.current.set(v,[h,b]);else continue}n.push(v)}if(t)for(let[v,h]of this.async_deriveds){let b=d.async_deriveds.get(v);b&&h.promise.then(b.resolve)}if(d.#e){var a=[...d.current.keys()].filter(v=>!this.current.has(v));if(a.length===0)t&&d.discard();else if(n.length>0){if(t)for(let v of this.#f)d.unskip_effect(v,h=>{(h.f&(Ve|Nt))!==0?d.schedule(h):d.#p([h])});d.activate();var o=new Set,l=new Map;for(var s of n)Va(s,a,o,l);l=new Map;var c=[...d.current.keys()].filter(v=>this.current.has(v)?this.current.get(v)[0]!==v.v:!0);if(c.length>0)for(let v of this.#v)(v.f&(je|Le|Cn))===0&&Ar(v,c,l)&&((v.f&(Nt|Ve))!==0?(ne(v,de),d.schedule(v)):d.#h.add(v));if(d.#l.length>0){d.apply();for(var u of d.#l)d.#k(u,[],[]);d.#l=[]}d.deactivate()}}}}increment(t,n){if(this.#a+=1,t){let r=this.#o.get(n)??0;this.#o.set(n,r+1)}}decrement(t,n){if(this.#a-=1,t){let r=this.#o.get(n)??0;r===1?this.#o.delete(n):this.#o.set(n,r-1)}this.#b||(this.#b=!0,rt(()=>{this.#b=!1,this.linked&&this.flush()}))}transfer_effects(t,n){for(let r of t)this.#h.add(r);for(let r of n)this.#c.add(r);t.clear(),n.clear()}oncommit(t){this.#s.add(t)}ondiscard(t){this.#r.add(t)}on_fork_commit(t){this.#i.add(t)}run_fork_commit_callbacks(){for(let t of this.#i)t(this);this.#i.clear()}settled(){return(this.#d??=ka()).promise}static ensure(){if(I===null){let t=I=new e;t.#y(),!Xn&&!en&&rt(()=>{t.#e||t.flush()})}return I}apply(){{Fe=null;return}}schedule(t){if(hr=t,t.b?.is_pending&&(t.f&(Ft|Rn|xr))!==0&&(t.f&gt)===0){t.b.defer_effect(t);return}for(var n=t;n.parent!==null;){n=n.parent;var r=n.f;if(Dt!==null&&n===A&&(U===null||(U.f&ve)===0))return;if((r&(at|Ze))!==0){if((r&ue)===0)return;n.f^=ue}}this.#l.push(n)}#y(){Pt===null?Zn=Pt=this:(Pt.#n=this,this.#t=Pt),Pt=this}#w(){var t=this.#t,n=this.#n;t===null?Zn=n:t.#n=n,n===null?Pt=t:n.#t=t,this.linked=!1}};function Y(e){var t=en;en=!0;try{for(var n;;){if(jo(),I===null)return n;I.flush()}}finally{en=t}}function Qo(){try{Io()}catch(e){ut(e,hr)}}var tt=null;function la(e){var t=e.length;if(t!==0){for(var n=0;n<t;){var r=e[n++];if((r.f&(je|Le))===0&&fn(r)&&(tt=new Set,jt(r),r.deps===null&&r.first===null&&r.nodes===null&&r.teardown===null&&r.ac===null&&li(r),tt?.size>0)){Ct.clear();for(let a of tt){if((a.f&(je|Le))!==0)continue;let o=[a],l=a.parent;for(;l!==null;)tt.has(l)&&(tt.delete(l),o.push(l)),l=l.parent;for(let s=o.length-1;s>=0;s--){let c=o[s];(c.f&(je|Le))===0&&jt(c)}}tt.clear()}}tt=null}}function Va(e,t,n,r){if(!n.has(e)&&(n.add(e),e.reactions!==null))for(let a of e.reactions){let o=a.f;(o&ve)!==0?Va(a,t,n,r):(o&(Nt|Ve))!==0&&(o&de)===0&&Ar(a,t,r)&&(ne(a,de),Rr(a))}}function Ar(e,t,n){let r=n.get(e);if(r!==void 0)return r;if(e.deps!==null)for(let a of e.deps){if(Vt.call(t,a))return!0;if((a.f&ve)!==0&&Ar(a,t,n))return n.set(a,!0),!0}return n.set(e,!1),!1}function Rr(e){I.schedule(e)}function Fa(e,t){if(!((e.f&Ze)!==0&&(e.f&ue)!==0)){(e.f&de)!==0?t.d.push(e):(e.f&Xe)!==0&&t.m.push(e),ne(e,ue);for(var n=e.first;n!==null;)Fa(n,t),n=n.next}}function za(e){ne(e,ue);for(var t=e.first;t!==null;)za(t),t=t.next}function el(e){let t=0,n=un(0),r;return()=>{Pr()&&(i(n),Dn(()=>(t===0&&(r=dn(()=>e(()=>tn(n)))),t+=1,()=>{rt(()=>{t-=1,t===0&&(r?.(),r=void 0,tn(n))})})))}}var tl=ht|Rt;function nl(e,t,n,r){new vr(e,t,n,r)}var vr=class{parent;is_pending=!1;transform_error;#e;#t=R?M:null;#n;#s;#r;#i=null;#a=null;#o=null;#d=null;#l=0;#v=0;#h=!1;#c=new Set;#u=new Set;#f=null;#b=el(()=>(this.#f=un(this.#l),()=>{this.#f=null}));constructor(t,n,r,a){this.#e=t,this.#n=n,this.#s=o=>{var l=A;l.b=this,l.f|=ar,r(o)},this.parent=A.b,this.transform_error=a??this.parent?.transform_error??(o=>o),this.#r=hn(()=>{if(R){let o=this.#t;At();let l=o.data===Sa;if(o.data.startsWith(ra)){let c=JSON.parse(o.data.slice(ra.length));this.#g(c)}else l?this.#k():this.#_()}else this.#x()},tl),R&&(this.#e=M)}#_(){try{this.#i=Ue(()=>this.#s(this.#e))}catch(t){this.error(t)}}#g(t){let n=this.#n.failed;n&&(this.#o=Ue(()=>{n(this.#e,()=>t,()=>()=>{})}))}#k(){let t=this.#n.pending;t&&(this.is_pending=!0,this.#a=Ue(()=>t(this.#e)),rt(()=>{var n=this.#d=document.createDocumentFragment(),r=He();n.append(r),this.#i=this.#p(()=>Ue(()=>this.#s(r))),this.#v===0&&(this.#e.before(n),this.#d=null,nn(this.#a,()=>{this.#a=null}),this.#m(I))}))}#x(){try{if(this.is_pending=this.has_pending_snippet(),this.#v=0,this.#l=0,this.#i=Ue(()=>{this.#s(this.#e)}),this.#v>0){var t=this.#d=document.createDocumentFragment();ui(this.#i,t);let n=this.#n.pending;this.#a=Ue(()=>n(this.#e))}else this.#m(I)}catch(n){this.error(n)}}#m(t){this.is_pending=!1,t.transfer_effects(this.#c,this.#u)}defer_effect(t){Na(t,this.#c,this.#u)}is_rendered(){return!this.is_pending&&(!this.parent||this.parent.is_rendered())}has_pending_snippet(){return!!this.#n.pending}#p(t){var n=A,r=U,a=ke;Qe(this.#r),Me(this.#r),zt(this.#r.ctx);try{return vt.ensure(),t()}catch(o){return Da(o),null}finally{Qe(n),Me(r),zt(a)}}#E(t,n){if(!this.has_pending_snippet()){this.parent&&this.parent.#E(t,n);return}this.#v+=t,this.#v===0&&(this.#m(n),this.#a&&nn(this.#a,()=>{this.#a=null}),this.#d&&(this.#e.before(this.#d),this.#d=null))}update_pending_count(t,n){this.#E(t,n),this.#l+=t,!(!this.#f||this.#h)&&(this.#h=!0,rt(()=>{this.#h=!1,this.#f&&An(this.#f,this.#l)}))}get_effect_pending(){return this.#b(),i(this.#f)}error(t){if(!this.#n.onerror&&!this.#n.failed)throw t;I?.is_fork?(this.#i&&I.skip_effect(this.#i),this.#a&&I.skip_effect(this.#a),this.#o&&I.skip_effect(this.#o),I.on_fork_commit(()=>{this.#y(t)})):this.#y(t)}#y(t){this.#i&&(he(this.#i),this.#i=null),this.#a&&(he(this.#a),this.#a=null),this.#o&&(he(this.#o),this.#o=null),R&&(me(this.#t),Cr(),me(Sr()));var n=this.#n.onerror;let r=this.#n.failed;var a=!1,o=!1;let l=()=>{if(a){Ko();return}a=!0,o&&Mo(),this.#o!==null&&nn(this.#o,()=>{this.#o=null}),this.#p(()=>{this.#x()})},s=c=>{try{o=!0,n?.(c,l),o=!1}catch(u){ut(u,this.#r&&this.#r.parent)}r&&(this.#o=this.#p(()=>{try{return Ue(()=>{var u=A;u.b=this,u.f|=ar,r(this.#e,()=>c,()=>l)})}catch(u){return ut(u,this.#r.parent),null}}))};rt(()=>{var c;try{c=this.transform_error(t)}catch(u){ut(u,this.#r&&this.#r.parent);return}c!==null&&typeof c=="object"&&typeof c.then=="function"?c.then(s,u=>ut(u,this.#r&&this.#r.parent)):s(c)})}};function ja(e,t,n,r){let a=Ir;var o=e.filter(h=>!h.settled);if(n.length===0&&o.length===0){r(t.map(a));return}var l=A,s=rl(),c=o.length===1?o[0].promise:o.length>1?Promise.all(o.map(h=>h.promise)):null;function u(h){if((l.f&je)===0){s();try{r(h)}catch(b){ut(b,l)}Tn()}}var d=Ha();if(n.length===0){c.then(()=>u(t.map(a))).finally(d);return}function v(){Promise.all(n.map(h=>al(h))).then(h=>u([...t.map(a),...h])).catch(h=>ut(h,l)).finally(d)}c?c.then(()=>{s(),v(),Tn()}):v()}function rl(){var e=A,t=U,n=ke,r=I;return function(o=!0){Qe(e),Me(t),zt(n),o&&(e.f&je)===0&&(r?.activate(),r?.apply())}}function Tn(e=!0){Qe(null),Me(null),zt(null),e&&I?.deactivate()}function Ha(){var e=A,t=e.b,n=I,r=t.is_rendered();return t.update_pending_count(1,n),n.increment(r,e),()=>{t.update_pending_count(-1,n),n.decrement(r,e)}}function Ir(e){var t=ve|de;return A!==null&&(A.f|=Rt),{ctx:ke,deps:null,effects:null,equals:Ea,f:t,fn:e,reactions:null,rv:0,v:ie,wv:0,parent:A,ac:null}}var mn=Symbol("obsolete");function al(e,t,n){let r=A;r===null&&To();var a=void 0,o=un(ie),l=!U,s=new Set;return gl(()=>{var c=A,u=ka();a=u.promise;try{Promise.resolve(e()).then(u.resolve,b=>{b!==In&&u.reject(b)}).finally(Tn)}catch(b){u.reject(b),Tn()}var d=I;if(l){if((c.f&gt)!==0)var v=Ha();if(r.b.is_rendered())d.async_deriveds.get(c)?.reject(mn);else for(let b of s.values())b.reject(mn);s.add(u),d.async_deriveds.set(c,u)}let h=(b,g=void 0)=>{v?.(),s.delete(u),g!==mn&&(d.activate(),g?(o.f|=dt,An(o,g)):((o.f&dt)!==0&&(o.f^=dt),An(o,b)),d.deactivate())};u.promise.then(h,b=>h(null,b||"unknown"))}),Ln(()=>{for(let c of s)c.reject(mn)}),new Promise(c=>{function u(d){function v(){d===a?c(o):u(a)}d.then(v,v)}u(a)})}function be(e){let t=Ir(e);return Ja(t),t}function il(e){var t=e.effects;if(t!==null){e.effects=null;for(var n=0;n<t.length;n+=1)he(t[n])}}function Or(e){var t,n=A,r=e.parent;if(!it&&r!==null&&e.v!==ie&&(r.f&(je|Le))!==0)return Ho(),e.v;Qe(r);try{e.f&=~Tt,il(e),t=ei(e)}finally{Qe(n)}return t}function Ba(e){var t=Or(e);if(!e.equals(t)&&(e.wv=Xa(),(!I?.is_fork||e.deps===null)&&(I!==null?(I.capture(e,t,!0),dr?.capture(e,t,!0)):e.v=t,e.deps===null))){ne(e,ue);return}it||(Fe!==null?(Pr()||I?.is_fork)&&Fe.set(e,t):$r(e))}function ol(e){if(e.effects!==null)for(let t of e.effects)(t.teardown||t.ac)&&(t.teardown?.(),t.ac?.abort(In),t.fn!==null&&(t.teardown=ft),t.ac=null,an(t,0),Dr(t))}function Ka(e){if(e.effects!==null)for(let t of e.effects)t.teardown&&t.fn!==null&&jt(t)}var $n=new Set,Ct=new Map,Ya=!1;function un(e,t){var n={f:0,v:e,reactions:null,equals:Ea,rv:0,wv:0};return n}function O(e,t){let n=un(e);return Ja(n),n}function Ga(e,t=!1,n=!0){let r=un(e);return t||(r.equals=Co),r}function _(e,t,n=!1){U!==null&&(!ze||(U.f&Cn)!==0)&&Aa()&&(U.f&(ve|Ve|Nt|Cn))!==0&&(De===null||!Vt.call(De,e))&&Do();let r=n?nt(t):t;return An(e,r,_n)}function An(e,t,n=null){if(!e.equals(t)){Ct.set(e,it?t:e.v);var r=vt.ensure();if(r.capture(e,t),(e.f&ve)!==0){let a=e;(e.f&de)!==0&&Or(a),Fe===null&&$r(a)}e.wv=Xa(),qa(e,de,n),A!==null&&(A.f&ue)!==0&&(A.f&(Ze|at))===0&&(Oe===null?ul([e]):Oe.push(e)),!r.is_fork&&$n.size>0&&!Ya&&ll()}return t}function ll(){Ya=!1;for(let e of $n){(e.f&ue)!==0&&ne(e,Xe);let t;try{t=fn(e)}catch{t=!0}t&&jt(e)}$n.clear()}function tn(e){_(e,e.v+1)}function qa(e,t,n){var r=e.reactions;if(r!==null)for(var a=r.length,o=0;o<a;o++){var l=r[o],s=l.f,c=(s&de)===0;if(c&&ne(l,t),(s&Cn)!==0)$n.add(l);else if((s&ve)!==0){var u=l;Fe?.delete(u),(s&Tt)===0&&(s&Pe&&(A===null||(A.f&Sn)===0)&&(l.f|=Tt),qa(u,Xe,n))}else if(c){var d=l;(s&Ve)!==0&&tt!==null&&tt.add(d),n!==null?n.push(d):Rr(d)}}}function sl(e,t){if(t){let n=document.body;e.autofocus=!0,rt(()=>{document.activeElement===n&&e.focus()})}}var sa=!1;function Wa(){sa||(sa=!0,document.addEventListener("reset",e=>{Promise.resolve().then(()=>{if(!e.defaultPrevented)for(let t of e.target.elements)t[Qt]?.()})},{capture:!0}))}function Pn(e){var t=U,n=A;Me(null),Qe(null);try{return e()}finally{Me(t),Qe(n)}}function cl(e,t,n,r=n){e.addEventListener(t,()=>Pn(n));let a=e[Qt];a?e[Qt]=()=>{a(),r(!0)}:e[Qt]=()=>r(!0),Wa()}var kn=!1,it=!1;function ca(e){it=e}var U=null,ze=!1;function Me(e){U=e}var A=null;function Qe(e){A=e}var De=null;function Ja(e){U!==null&&(De===null?De=[e]:De.push(e))}var _e=null,Ce=0,Oe=null;function ul(e){Oe=e}var Za=1,kt=0,St=kt;function ua(e){St=e}function Xa(){return++Za}function fn(e){var t=e.f;if((t&de)!==0)return!0;if(t&ve&&(e.f&=~Tt),(t&Xe)!==0){for(var n=e.deps,r=n.length,a=0;a<r;a++){var o=n[a];if(fn(o)&&Ba(o),o.wv>e.wv)return!0}(t&Pe)!==0&&Fe===null&&ne(e,ue)}return!1}function Qa(e,t,n=!0){var r=e.reactions;if(r!==null&&!(De!==null&&Vt.call(De,e)))for(var a=0;a<r.length;a++){var o=r[a];(o.f&ve)!==0?Qa(o,t,!1):t===o&&(n?ne(o,de):(o.f&ue)!==0&&ne(o,Xe),Rr(o))}}function ei(e){var t=_e,n=Ce,r=Oe,a=U,o=De,l=ke,s=ze,c=St,u=e.f;_e=null,Ce=0,Oe=null,U=(u&(Ze|at))===0?e:null,De=null,zt(e.ctx),ze=!1,St=++kt,e.ac!==null&&(Pn(()=>{e.ac.abort(In)}),e.ac=null);try{e.f|=Sn;var d=e.fn,v=d();e.f|=gt;var h=e.deps,b=I?.is_fork;if(_e!==null){var g;if(b||an(e,Ce),h!==null&&Ce>0)for(h.length=Ce+_e.length,g=0;g<_e.length;g++)h[Ce+g]=_e[g];else e.deps=h=_e;if(Pr()&&(e.f&Pe)!==0)for(g=Ce;g<h.length;g++)(h[g].reactions??=[]).push(e)}else!b&&h!==null&&Ce<h.length&&(an(e,Ce),h.length=Ce);if(Aa()&&Oe!==null&&!ze&&h!==null&&(e.f&(ve|Xe|de))===0)for(g=0;g<Oe.length;g++)Qa(Oe[g],e);if(a!==null&&a!==e){if(kt++,a.deps!==null)for(let E=0;E<n;E+=1)a.deps[E].rv=kt;if(t!==null)for(let E of t)E.rv=kt;Oe!==null&&(r===null?r=Oe:r.push(...Oe))}return(e.f&dt)!==0&&(e.f^=dt),v}catch(E){return Da(E)}finally{e.f^=Sn,_e=t,Ce=n,Oe=r,U=a,De=o,zt(l),ze=s,St=c}}function fl(e,t){let n=t.reactions;if(n!==null){var r=po.call(n,e);if(r!==-1){var a=n.length-1;a===0?n=t.reactions=null:(n[r]=n[a],n.pop())}}if(n===null&&(t.f&ve)!==0&&(_e===null||!Vt.call(_e,t))){var o=t;(o.f&Pe)!==0&&(o.f^=Pe,o.f&=~Tt),o.v!==ie&&$r(o),ol(o),an(o,0)}}function an(e,t){var n=e.deps;if(n!==null)for(var r=t;r<n.length;r++)fl(e,n[r])}function jt(e){var t=e.f;if((t&je)===0){ne(e,ue);var n=A,r=kn;A=e,kn=!0;try{(t&(Ve|xr))!==0?bl(e):Dr(e),ii(e);var a=ei(e);e.teardown=typeof a=="function"?a:null,e.wv=Za;var o}finally{kn=r,A=n}}}async function xt(){await Promise.resolve(),Y()}function i(e){var t=e.f,n=(t&ve)!==0;if(U!==null&&!ze){var r=A!==null&&(A.f&je)!==0;if(!r&&(De===null||!Vt.call(De,e))){var a=U.deps;if((U.f&Sn)!==0)e.rv<kt&&(e.rv=kt,_e===null&&a!==null&&a[Ce]===e?Ce++:_e===null?_e=[e]:_e.push(e));else{(U.deps??=[]).push(e);var o=e.reactions;o===null?e.reactions=[U]:Vt.call(o,U)||o.push(U)}}}if(it&&Ct.has(e))return Ct.get(e);if(n){var l=e;if(it){var s=l.v;return((l.f&ue)===0&&l.reactions!==null||ni(l))&&(s=Or(l)),Ct.set(l,s),s}var c=(l.f&Pe)===0&&!ze&&U!==null&&(kn||(U.f&Pe)!==0),u=(l.f&gt)===0;fn(l)&&(c&&(l.f|=Pe),Ba(l)),c&&!u&&(Ka(l),ti(l))}if(Fe?.has(e))return Fe.get(e);if((e.f&dt)!==0)throw e.v;return e.v}function ti(e){if(e.f|=Pe,e.deps!==null)for(let t of e.deps)(t.reactions??=[]).push(e),(t.f&ve)!==0&&(t.f&Pe)===0&&(Ka(t),ti(t))}function ni(e){if(e.v===ie)return!0;if(e.deps===null)return!1;for(let t of e.deps)if(Ct.has(t)||(t.f&ve)!==0&&ni(t))return!0;return!1}function dn(e){var t=ze;try{return ze=!0,e()}finally{ze=t}}function dl(e){A===null&&(U===null&&Ro(),Ao()),it&&$o()}function hl(e,t){var n=t.last;n===null?t.last=t.first=e:(n.next=e,e.prev=n,t.last=e)}function Be(e,t){var n=A;n!==null&&(n.f&Le)!==0&&(e|=Le);var r={ctx:ke,deps:null,nodes:null,f:e|de|Pe,first:null,fn:t,last:null,next:null,parent:n,b:n&&n.b,prev:null,teardown:null,wv:0,ac:null};I?.register_created_effect(r);var a=r;if((e&Ft)!==0)Dt!==null?Dt.push(r):vt.ensure().schedule(r);else if(t!==null){try{jt(r)}catch(l){throw he(r),l}a.deps===null&&a.teardown===null&&a.nodes===null&&a.first===a.last&&(a.f&Rt)===0&&(a=a.first,(e&Ve)!==0&&(e&ht)!==0&&a!==null&&(a.f|=ht))}if(a!==null&&(a.parent=n,n!==null&&hl(a,n),U!==null&&(U.f&ve)!==0&&(e&at)===0)){var o=U;(o.effects??=[]).push(a)}return r}function Pr(){return U!==null&&!ze}function Ln(e){let t=Be(Rn,null);return ne(t,ue),t.teardown=e,t}function Se(e){dl();var t=A.f,n=!U&&(t&Ze)!==0&&(t&gt)===0;if(n){var r=ke;(r.e??=[]).push(e)}else return ri(e)}function ri(e){return Be(Ft|ko,e)}function vl(e){vt.ensure();let t=Be(at|Rt,e);return()=>{he(t)}}function pl(e){vt.ensure();let t=Be(at|Rt,e);return(n={})=>new Promise(r=>{n.outro?nn(t,()=>{he(t),r(void 0)}):(he(t),r(void 0))})}function Lr(e){return Be(Ft,e)}function gl(e){return Be(Nt|Rt,e)}function Dn(e,t=0){return Be(Rn|t,e)}function pe(e,t=[],n=[],r=[]){ja(r,t,n,a=>{Be(Rn,()=>e(...a.map(i)))})}function hn(e,t=0){var n=Be(Ve|t,e);return n}function ai(e,t=0){var n=Be(xr|t,e);return n}function Ue(e){return Be(Ze|Rt,e)}function ii(e){var t=e.teardown;if(t!==null){let n=it,r=U;ca(!0),Me(null);try{t.call(null)}finally{ca(n),Me(r)}}}function Dr(e,t=!1){var n=e.first;for(e.first=e.last=null;n!==null;){let a=n.ac;a!==null&&Pn(()=>{a.abort(In)});var r=n.next;(n.f&at)!==0?n.parent=null:he(n,t),n=r}}function bl(e){for(var t=e.first;t!==null;){var n=t.next;(t.f&Ze)===0&&he(t),t=n}}function he(e,t=!0){var n=!1;(t||(e.f&_o)!==0)&&e.nodes!==null&&e.nodes.end!==null&&(oi(e.nodes.start,e.nodes.end),n=!0),ne(e,ir),Dr(e,t&&!n),an(e,0);var r=e.nodes&&e.nodes.t;if(r!==null)for(let o of r)o.stop();ii(e),e.f^=ir,e.f|=je;var a=e.parent;a!==null&&a.first!==null&&li(e),e.next=e.prev=e.teardown=e.ctx=e.deps=e.fn=e.nodes=e.ac=e.b=null}function oi(e,t){for(;e!==null;){var n=e===t?null:et(e);e.remove(),e=n}}function li(e){var t=e.parent,n=e.prev,r=e.next;n!==null&&(n.next=r),r!==null&&(r.prev=n),t!==null&&(t.first===e&&(t.first=r),t.last===e&&(t.last=n))}function nn(e,t,n=!0){var r=[];si(e,r,!0);var a=()=>{n&&he(e),t&&t()},o=r.length;if(o>0){var l=()=>--o||a();for(var s of r)s.out(l)}else a()}function si(e,t,n){if((e.f&Le)===0){e.f^=Le;var r=e.nodes&&e.nodes.t;if(r!==null)for(let s of r)(s.is_global||n)&&t.push(s);for(var a=e.first;a!==null;){var o=a.next;if((a.f&at)===0){var l=(a.f&ht)!==0||(a.f&Ze)!==0&&(e.f&Ve)!==0;si(a,t,l?n:!1)}a=o}}}function ml(e){ci(e,!0)}function ci(e,t){if((e.f&Le)!==0){e.f^=Le,(e.f&ue)===0&&(ne(e,de),vt.ensure().schedule(e));for(var n=e.first;n!==null;){var r=n.next,a=(n.f&ht)!==0||(n.f&Ze)!==0;ci(n,a?t:!1),n=r}var o=e.nodes&&e.nodes.t;if(o!==null)for(let l of o)(l.is_global||t)&&l.in()}}function ui(e,t){if(e.nodes)for(var n=e.nodes.start,r=e.nodes.end;n!==null;){var a=n===r?null:et(n);t.append(n),n=a}}function fa(e){let t={get:n=>Zt(t.store)[n],set:(n,r)=>{typeof n=="string"?Object.assign(Zt(t.store),{[n]:r}):Object.assign(Zt(t.store),n),t.store.set(Zt(t.store))},store:Jo(e)};return t}globalThis.$altcha=globalThis.$altcha||{algorithms:new Map,defaults:fa({}),i18n:fa({}),instances:new Set,plugins:new Set};var yl={ariaLinkLabel:"Altcha (official website)",cancel:"Cancel",enterCode:"Enter code",enterCodeAria:"Enter code you hear. Press Space to play audio.",enterCodeFromImage:"To proceed, please enter the code from the image below.",error:"Verification failed. Try again later.",expired:"Verification expired. Try again.",footer:'Protected by <a href="https://altcha.org/" tabindex="-1" target="_blank" aria-label="Altcha (official website)">ALTCHA</a>',getAudioChallenge:"Get an audio challenge",label:"I'm not a robot",loading:"Loading...",reload:"Reload",verify:"Verify",verificationRequired:"Verification required!",verified:"Verified",verifying:"Verifying...",waitAlert:"Verifying... please wait."};"$altcha"in globalThis&&globalThis.$altcha.i18n.set("en",yl);var wl="5";typeof window<"u"&&((window.__svelte??={}).v??=new Set).add(wl);var Xt=Symbol("events"),fi=new Set,pr=new Set;function di(e,t,n,r={}){function a(o){if(r.capture||gr.call(t,o),!o.cancelBubble)return Pn(()=>n?.call(this,o))}return e.startsWith("pointer")||e.startsWith("touch")||e==="wheel"?rt(()=>{t.addEventListener(e,a,r)}):t.addEventListener(e,a,r),a}function ce(e,t,n,r,a){var o={capture:r,passive:a},l=di(e,t,n,o);(t===document.body||t===window||t===document||t instanceof HTMLMediaElement)&&Ln(()=>{t.removeEventListener(e,l,o)})}function Mn(e,t,n){(t[Xt]??={})[e]=n}function Nn(e){for(var t=0;t<e.length;t++)fi.add(e[t]);for(var n of pr)n(e)}var da=null;function gr(e){var t=this,n=t.ownerDocument,r=e.type,a=e.composedPath?.()||[],o=a[0]||e.target;da=e;var l=0,s=da===e&&e[Xt];if(s){var c=a.indexOf(s);if(c!==-1&&(t===document||t===window)){e[Xt]=t;return}var u=a.indexOf(t);if(u===-1)return;c<=u&&(l=c)}if(o=a[l]||e.target,o!==t){rn(e,"currentTarget",{configurable:!0,get(){return o||n}});var d=U,v=A;Me(null),Qe(null);try{for(var h,b=[];o!==null;){var g=o.assignedSlot||o.parentNode||o.host||null;try{var E=o[Xt]?.[r];E!=null&&(!o.disabled||e.target===o)&&E.call(o,e)}catch($){h?b.push($):h=$}if(e.cancelBubble||g===t||g===null)break;o=g}if(h){for(let $ of b)queueMicrotask(()=>{throw $});throw h}}finally{e[Xt]=t,delete e.currentTarget,Me(d),Qe(v)}}}var _l=globalThis?.window?.trustedTypes&&globalThis.window.trustedTypes.createPolicy("svelte-trusted-html",{createHTML:e=>e});function kl(e){return _l?.createHTML(e)??e}function hi(e){var t=Tr("template");return t.innerHTML=kl(e.replaceAll("<!>","<!---->")),t.content}function $e(e,t){var n=A;n.nodes===null&&(n.nodes={start:e,end:t,a:null,t:null})}function Z(e,t){var n=(t&No)!==0,r=(t&Uo)!==0,a,o=!e.startsWith("<!>");return()=>{if(R)return $e(M,null),M;a===void 0&&(a=hi(o?e:"<!>"+e),n||(a=Te(a)));var l=r||Oa?document.importNode(a,!0):a.cloneNode(!0);if(n){var s=Te(l),c=l.lastChild;$e(s,c)}else $e(l,l);return l}}function xl(e,t,n="svg"){var r=!e.startsWith("<!>"),a=`<${n}>${r?e:"<!>"+e}</${n}>`,o;return()=>{if(R)return $e(M,null),M;if(!o){var l=hi(a),s=Te(l);o=Te(s)}var c=o.cloneNode(!0);return $e(c,c),c}}function Mr(e,t){return xl(e,t,"svg")}function yn(e=""){if(!R){var t=He(e+"");return $e(t,t),t}var n=M;return n.nodeType!==ln?(n.before(n=He()),me(n)):On(n),$e(n,n),n}function ha(){if(R)return $e(M,null),M;var e=document.createDocumentFragment(),t=document.createComment(""),n=He();return e.append(t,n),$e(t,n),e}function D(e,t){if(R){var n=A;((n.f&gt)===0||n.nodes.end===null)&&(n.nodes.end=M),At();return}e!==null&&e.before(t)}function El(e){return e.endsWith("capture")&&e!=="gotpointercapture"&&e!=="lostpointercapture"}var Cl=["beforeinput","click","change","dblclick","contextmenu","focusin","focusout","input","keydown","keyup","mousedown","mousemove","mouseout","mouseover","mouseup","pointerdown","pointermove","pointerout","pointerover","pointerup","touchend","touchmove","touchstart"];function Sl(e){return Cl.includes(e)}var Tl={formnovalidate:"formNoValidate",ismap:"isMap",nomodule:"noModule",playsinline:"playsInline",readonly:"readOnly",defaultvalue:"defaultValue",defaultchecked:"defaultChecked",srcobject:"srcObject",novalidate:"noValidate",allowfullscreen:"allowFullscreen",disablepictureinpicture:"disablePictureInPicture",disableremoteplayback:"disableRemotePlayback"};function $l(e){return e=e.toLowerCase(),Tl[e]??e}var Al=["touchstart","touchmove"];function Rl(e){return Al.includes(e)}function We(e,t){var n=t==null?"":typeof t=="object"?`${t}`:t;n!==(e[sr]??=e.nodeValue)&&(e[sr]=n,e.nodeValue=`${n}`)}function vi(e,t){return pi(e,t)}function Il(e,t){ur(),t.intro=t.intro??!1;let n=t.target,r=R,a=M;try{for(var o=Te(n);o&&(o.nodeType!==sn||o.data!==Er);)o=et(o);if(!o)throw $t;Je(!0),me(o);let l=pi(e,{...t,anchor:o});return Je(!1),l}catch(l){if(l instanceof Error&&l.message.split(`
`).some(s=>s.startsWith("https://svelte.dev/e/")))throw l;return l!==$t&&console.warn("Failed to hydrate: ",l),t.recover===!1&&Oo(),ur(),Go(n),Je(!1),vi(e,t)}finally{Je(r),me(a)}}var wn=new Map;function pi(e,{target:t,anchor:n,props:r={},events:a,context:o,intro:l=!0,transformError:s}){ur();var c=void 0,u=pl(()=>{var d=n??t.appendChild(He());nl(d,{pending:()=>{}},b=>{ot({});var g=ke;if(o&&(g.c=o),a&&(r.$$events=a),R&&$e(b,null),c=e(b,r)||{},R&&(A.nodes.end=M,M===null||M.nodeType!==sn||M.data!==Ta))throw cn(),$t;lt()},s);var v=new Set,h=b=>{for(var g=0;g<b.length;g++){var E=b[g];if(!v.has(E)){v.add(E);var $=Rl(E);for(let re of[t,document]){var P=wn.get(re);P===void 0&&(P=new Map,wn.set(re,P));var oe=P.get(E);oe===void 0?(re.addEventListener(E,gr,{passive:$}),P.set(E,1)):P.set(E,oe+1)}}}};return h(go(fi)),pr.add(h),()=>{for(var b of v)for(let $ of[t,document]){var g=wn.get($),E=g.get(b);--E==0?($.removeEventListener(b,gr),g.delete(b),g.size===0&&wn.delete($)):g.set(b,E)}pr.delete(h),d!==n&&d.parentNode?.removeChild(d)}});return br.set(c,u),c}var br=new WeakMap;function Ol(e,t){let n=br.get(e);return n?(br.delete(e),n(t)):Promise.resolve()}var Ht=class{anchor;#e=new Map;#t=new Map;#n=new Map;#s=new Set;#r=!0;constructor(t,n=!0){this.anchor=t,this.#r=n}#i=t=>{if(this.#e.has(t)){var n=this.#e.get(t),r=this.#t.get(n);if(r)ml(r),this.#s.delete(n);else{var a=this.#n.get(n);a&&(this.#t.set(n,a.effect),this.#n.delete(n),a.fragment.lastChild.remove(),this.anchor.before(a.fragment),r=a.effect)}for(let[o,l]of this.#e){if(this.#e.delete(o),o===t)break;let s=this.#n.get(l);s&&(he(s.effect),this.#n.delete(l))}for(let[o,l]of this.#t){if(o===n||this.#s.has(o))continue;let s=()=>{if(Array.from(this.#e.values()).includes(o)){var u=document.createDocumentFragment();ui(l,u),u.append(He()),this.#n.set(o,{effect:l,fragment:u})}else he(l);this.#s.delete(o),this.#t.delete(o)};this.#r||!r?(this.#s.add(o),nn(l,s,!1)):s()}}};#a=t=>{this.#e.delete(t);let n=Array.from(this.#e.values());for(let[r,a]of this.#n)n.includes(r)||(he(a.effect),this.#n.delete(r))};ensure(t,n){var r=I,a=qo();if(n&&!this.#t.has(t)&&!this.#n.has(t))if(a){var o=document.createDocumentFragment(),l=He();o.append(l),this.#n.set(t,{effect:Ue(()=>n(l)),fragment:o})}else this.#t.set(t,Ue(()=>n(this.anchor)));if(this.#e.set(r,t),a){for(let[s,c]of this.#t)s===t?r.unskip_effect(c):r.skip_effect(c);for(let[s,c]of this.#n)s===t?r.unskip_effect(c.effect):r.skip_effect(c.effect);r.oncommit(this.#i),r.ondiscard(this.#a)}else R&&(this.anchor=M),this.#i(r)}};function Pl(e,t,...n){var r=new Ht(e);hn(()=>{let a=t()??null;r.ensure(a,a&&(o=>a(o,...n)))},ht)}function Nr(e){ke===null&&So(),Se(()=>{let t=dn(e);if(typeof t=="function")return t})}function se(e,t,n=!1){var r;R&&(r=M,At());var a=new Ht(e),o=n?ht:0;function l(s,c){if(R){var u=Ia(r);if(s!==parseInt(u.substring(1))){var d=Sr();me(d),a.anchor=d,Je(!1),a.ensure(s,c),Je(!0);return}}a.ensure(s,c)}hn(()=>{var s=!1;t((c,u=0)=>{s=!0,l(u,c)}),s||l(-1,null)},o)}var Ll=Symbol("NaN");function Dl(e,t,n){R&&At();var r=new Ht(e);hn(()=>{var a=t();a!==a&&(a=Ll),r.ensure(a,n)})}function gi(e,t,n=!1,r=!1,a=!1,o=!1){var l=e,s="";if(n){var c=e;R&&(l=me(Te(c)))}pe(()=>{var u=A;if(s===(s=t()??"")){R&&At();return}if(n&&!R){u.nodes=null,c.innerHTML=s,s!==""&&$e(Te(c),c.lastChild);return}if(u.nodes!==null&&(oi(u.nodes.start,u.nodes.end),u.nodes=null),s!==""){if(R){M.data;for(var d=At(),v=d;d!==null&&(d.nodeType!==sn||d.data!=="");)v=d,d=et(d);if(d===null)throw cn(),$t;$e(M,v),l=me(d);return}var h=r?Vo:a?Fo:void 0,b=Tr(r?"svg":a?"math":"template",h);b.innerHTML=s;var g=r||a?b:b.content;if($e(Te(g),g.lastChild),r||a)for(;Te(g);)l.before(Te(g));else l.before(g)}})}function Ml(e,t,n){var r;R&&(r=M,At());var a=new Ht(e);hn(()=>{var o=t()??null;if(R){var l=Ia(r),s=l===Er,c=o!==null;if(s!==c){var u=Sr();me(u),a.anchor=u,Je(!1),a.ensure(o,o&&(d=>n(d,o))),Je(!0);return}}a.ensure(o,o&&(d=>n(d,o)))},ht)}function Nl(e,t){var n=void 0,r;ai(()=>{n!==(n=t())&&(r&&(he(r),r=null),n&&(r=Ue(()=>{Lr(()=>n(e))})))})}function bi(e){var t,n,r="";if(typeof e=="string"||typeof e=="number")r+=e;else if(typeof e=="object")if(Array.isArray(e)){var a=e.length;for(t=0;t<a;t++)e[t]&&(n=bi(e[t]))&&(r&&(r+=" "),r+=n)}else for(n in e)e[n]&&(r&&(r+=" "),r+=n);return r}function Ul(){for(var e,t,n=0,r="",a=arguments.length;n<a;n++)(e=arguments[n])&&(t=bi(e))&&(r&&(r+=" "),r+=t);return r}function Vl(e){return typeof e=="object"?Ul(e):e??""}var va=[...` 	
\r\f\xA0\v\uFEFF`];function Fl(e,t,n){var r=e==null?"":""+e;if(n){for(var a of Object.keys(n))if(n[a])r=r?r+" "+a:a;else if(r.length)for(var o=a.length,l=0;(l=r.indexOf(a,l))>=0;){var s=l+o;(l===0||va.includes(r[l-1]))&&(s===r.length||va.includes(r[s]))?r=(l===0?"":r.substring(0,l))+r.substring(s+1):l=s}}return r===""?null:r}function pa(e,t=!1){var n=t?" !important;":";",r="";for(var a of Object.keys(e)){var o=e[a];o!=null&&o!==""&&(r+=" "+a+": "+o+n)}return r}function Qn(e){return e[0]!=="-"||e[1]!=="-"?e.toLowerCase():e}function zl(e,t){if(t){var n="",r,a;if(Array.isArray(t)?(r=t[0],a=t[1]):r=t,e){e=String(e).replaceAll(/\s*\/\*.*?\*\/\s*/g,"").trim();var o=!1,l=0,s=!1,c=[];r&&c.push(...Object.keys(r).map(Qn)),a&&c.push(...Object.keys(a).map(Qn));var u=0,d=-1;let E=e.length;for(var v=0;v<E;v++){var h=e[v];if(s?h==="/"&&e[v-1]==="*"&&(s=!1):o?o===h&&(o=!1):h==="/"&&e[v+1]==="*"?s=!0:h==='"'||h==="'"?o=h:h==="("?l++:h===")"&&l--,!s&&o===!1&&l===0){if(h===":"&&d===-1)d=v;else if(h===";"||v===E-1){if(d!==-1){var b=Qn(e.substring(u,d).trim());if(!c.includes(b)){h!==";"&&v++;var g=e.substring(u,v).trim();n+=" "+g+";"}}u=v+1,d=-1}}}}return r&&(n+=pa(r)),a&&(n+=pa(a,!0)),n=n.trim(),n===""?null:n}return e==null?null:String(e)}function jl(e,t,n,r,a,o){var l=e[or];if(R||l!==n||l===void 0){var s=Fl(n,r,o);(!R||s!==e.getAttribute("class"))&&(s==null?e.removeAttribute("class"):t?e.className=s:e.setAttribute("class",s)),e[or]=n}else if(o&&a!==o)for(var c in o){var u=!!o[c];(a==null||u!==!!a[c])&&e.classList.toggle(c,u)}return o}function er(e,t={},n,r){for(var a in n){var o=n[a];t[a]!==o&&(n[a]==null?e.style.removeProperty(a):e.style.setProperty(a,o,r))}}function Hl(e,t,n,r){var a=e[lr];if(R||a!==t){var o=zl(t,r);(!R||o!==e.getAttribute("style"))&&(o==null?e.removeAttribute("style"):e.style.cssText=o),e[lr]=t}else r&&(Array.isArray(r)?(er(e,n?.[0],r[0]),er(e,n?.[1],r[1],"important")):er(e,n,r));return r}function mr(e,t,n=!1){if(e.multiple){if(t==null)return;if(!wa(t))return Bo();for(var r of e.options)r.selected=t.includes(ga(r));return}for(r of e.options){var a=ga(r);if(Yo(a,t)){r.selected=!0;return}}(!n||t!==void 0)&&(e.selectedIndex=-1)}function Bl(e){var t=new MutationObserver(()=>{mr(e,e.__value)});t.observe(e,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["value"]}),Ln(()=>{t.disconnect()})}function ga(e){return"__value"in e?e.__value:e.value}var Wt=Symbol("class"),Jt=Symbol("style"),mi=Symbol("is custom element"),yi=Symbol("is html"),Kl=on?"link":"LINK",Yl=on?"input":"INPUT",Gl=on?"option":"OPTION",ql=on?"select":"SELECT",Wl=on?"progress":"PROGRESS";function Ur(e){if(R){var t=!1,n=()=>{if(!t){if(t=!0,e.hasAttribute("value")){var r=e.value;j(e,"value",null),e.value=r}if(e.hasAttribute("checked")){var a=e.checked;j(e,"checked",null),e.checked=a}}};e[Qt]=n,rt(n),Wa()}}function Jl(e,t){var n=Vr(e);n.value===(n.value=t??void 0)||e.value===t&&(t!==0||e.nodeName!==Wl)||(e.value=t??"")}function Zl(e,t){t?e.hasAttribute("selected")||e.setAttribute("selected",""):e.removeAttribute("selected")}function j(e,t,n,r){var a=Vr(e);R&&(a[t]=e.getAttribute(t),t==="src"||t==="srcset"||t==="href"&&e.nodeName===Kl)||a[t]!==(a[t]=n)&&(t==="loading"&&(e[Eo]=n),n==null?e.removeAttribute(t):typeof n!="string"&&wi(e).includes(t)?e[t]=n:e.setAttribute(t,n))}function Xl(e,t,n,r,a=!1,o=!1){if(R&&a&&e.nodeName===Yl){var l=e,s=l.type==="checkbox"?"defaultChecked":"defaultValue";s in n||Ur(l)}var c=Vr(e),u=c[mi],d=!c[yi];let v=R&&u;v&&Je(!1);var h=t||{},b=e.nodeName===Gl;for(var g in t)g in n||(n[g]=null);n.class?n.class=Vl(n.class):n[Wt]&&(n.class=null),n[Jt]&&(n.style??=null);var E=wi(e);for(let x in n){let L=n[x];if(b&&x==="value"&&L==null){e.value=e.__value="",h[x]=L;continue}if(x==="class"){var $=e.namespaceURI==="http://www.w3.org/1999/xhtml";jl(e,$,L,r,t?.[Wt],n[Wt]),h[x]=L,h[Wt]=n[Wt];continue}if(x==="style"){Hl(e,L,t?.[Jt],n[Jt]),h[x]=L,h[Jt]=n[Jt];continue}var P=h[x];if(!(L===P&&!(L===void 0&&e.hasAttribute(x)))){h[x]=L;var oe=x[0]+x[1];if(oe!=="$$")if(oe==="on"){let ee={},V="$$"+x,N=x.slice(2);var re=Sl(N);if(El(N)&&(N=N.slice(0,-7),ee.capture=!0),!re&&P){if(L!=null)continue;e.removeEventListener(N,h[V],ee),h[V]=null}if(re)Mn(N,e,L),Nn([N]);else if(L!=null){let Ne=function(ge){h[x].call(this,ge)};var le=Ne;h[V]=di(N,e,Ne,ee)}}else if(x==="style")j(e,x,L);else if(x==="autofocus")sl(e,!!L);else if(!u&&(x==="__value"||x==="value"&&L!=null))e.value=e.__value=L;else if(x==="selected"&&b)Zl(e,L);else{var H=x;d||(H=$l(H));var Ke=H==="defaultValue"||H==="defaultChecked";if(L==null&&!u&&!Ke)if(c[x]=null,H==="value"||H==="checked"){let ee=e,V=t===void 0;if(H==="value"){let N=ee.defaultValue;ee.removeAttribute(H),ee.defaultValue=N,ee.value=ee.__value=V?N:null}else{let N=ee.defaultChecked;ee.removeAttribute(H),ee.defaultChecked=N,ee.checked=V?N:!1}}else e.removeAttribute(x);else Ke||E.includes(H)&&(u||typeof L!="string")?(e[H]=L,H in c&&(c[H]=ie)):typeof L!="function"&&j(e,H,L)}}}return v&&Je(!0),h}function Un(e,t,n=[],r=[],a=[],o,l=!1,s=!1){ja(a,n,r,c=>{var u=void 0,d={},v=e.nodeName===ql,h=!1;if(ai(()=>{var g=t(...c.map(i)),E=Xl(e,u,g,o,l,s);h&&v&&"value"in g&&mr(e,g.value);for(let P of Object.getOwnPropertySymbols(d))g[P]||he(d[P]);for(let P of Object.getOwnPropertySymbols(g)){var $=g[P];P.description===zo&&(!u||$!==u[P])&&(d[P]&&he(d[P]),d[P]=Ue(()=>Nl(e,()=>$))),E[P]=$}u=E}),v){var b=e;Lr(()=>{mr(b,u.value,!0),Bl(b)})}h=!0})}function Vr(e){return e[xa]??={[mi]:e.nodeName.includes("-"),[yi]:e.namespaceURI===$a}}var ba=new Map;function wi(e){var t=e.getAttribute("is")||e.nodeName,n=ba.get(t);if(n)return n;ba.set(t,n=[]);for(var r,a=e,o=Element.prototype;o!==a;){r=bo(a);for(var l in r)r[l].set&&l!=="innerHTML"&&l!=="textContent"&&l!=="innerText"&&n.push(l);a=_a(a)}return n}function Ql(e,t,n=t){var r=new WeakSet;cl(e,"input",async a=>{var o=a?e.defaultValue:e.value;if(o=tr(e)?nr(o):o,n(o),I!==null&&r.add(I),await xt(),o!==(o=t())){var l=e.selectionStart,s=e.selectionEnd,c=e.value.length;if(e.value=o??"",s!==null){var u=e.value.length;l===s&&s===c&&u>c?(e.selectionStart=u,e.selectionEnd=u):(e.selectionStart=l,e.selectionEnd=Math.min(s,u))}}}),(R&&e.defaultValue!==e.value||dn(t)==null&&e.value)&&(n(tr(e)?nr(e.value):e.value),I!==null&&r.add(I)),Dn(()=>{var a=t();if(e===document.activeElement){var o=I;if(r.has(o))return}tr(e)&&a===nr(e.value)||e.type==="date"&&!a&&!e.value||a!==e.value&&(e.value=a??"")})}function tr(e){var t=e.type;return t==="number"||t==="range"}function nr(e){return e===""?null:+e}function rr(e,t){return e===t||e?.[Ut]===t}function pt(e={},t,n,r){var a=ke.r,o=A;return Lr(()=>{var l,s;return Dn(()=>{l=s,s=[],dn(()=>{rr(n(...s),e)||(t(e,...s),l&&rr(n(...l),e)&&t(null,...l))})}),()=>{let c=o;for(;c!==a&&c.parent!==null&&c.parent.f&ir;)c=c.parent;let u=()=>{s&&rr(n(...s),e)&&t(null,...s)},d=c.teardown;c.teardown=()=>{u(),d?.()}}}),e}var es={get(e,t){if(!e.exclude.includes(t))return e.props[t]},set(e,t){return!1},getOwnPropertyDescriptor(e,t){if(!e.exclude.includes(t)&&t in e.props)return{enumerable:!0,configurable:!0,value:e.props[t]}},has(e,t){return e.exclude.includes(t)?!1:t in e.props},ownKeys(e){return Reflect.ownKeys(e.props).filter(t=>!e.exclude.includes(t))}};function Vn(e,t,n){return new Proxy({props:e,exclude:t},es)}function J(e,t,n,r){var a=r,o=!0,l=()=>(o&&(o=!1,a=r),a),s;s=e[t],s===void 0&&r!==void 0&&(s=l());var c;c=()=>{var h=e[t];return h===void 0?l():(o=!0,h)};var u=!1,d=Ir(()=>(u=!1,c())),v=A;return(function(h,b){if(arguments.length>0){let g=b?i(d):h;return _(d,g),u=!0,a!==void 0&&(a=g),h}return it&&u||(v.f&je)!==0?d.v:i(d)})}function ts(e){return new yr(e)}var yr=class{#e;#t;constructor(t){var n=new Map,r=(o,l)=>{var s=Ga(l,!1,!1);return n.set(o,s),s};let a=new Proxy({...t.props||{},$$events:{}},{get(o,l){return i(n.get(l)??r(l,Reflect.get(o,l)))},has(o,l){return l===xo?!0:(i(n.get(l)??r(l,Reflect.get(o,l))),Reflect.has(o,l))},set(o,l,s){return _(n.get(l)??r(l,s),s),Reflect.set(o,l,s)}});this.#t=(t.hydrate?Il:vi)(t.component,{target:t.target,anchor:t.anchor,props:a,context:t.context,intro:t.intro??!1,recover:t.recover,transformError:t.transformError}),(!t?.props?.$$host||t.sync===!1)&&Y(),this.#e=a.$$events;for(let o of Object.keys(this.#t))o==="$set"||o==="$destroy"||o==="$on"||rn(this,o,{get(){return this.#t[o]},set(l){this.#t[o]=l},enumerable:!0});this.#t.$set=o=>{Object.assign(a,o)},this.#t.$destroy=()=>{Ol(this.#t)}}$set(t){this.#t.$set(t)}$on(t,n){this.#e[t]=this.#e[t]||[];let r=(...a)=>n.call(this,...a);return this.#e[t].push(r),()=>{this.#e[t]=this.#e[t].filter(a=>a!==r)}}$destroy(){this.#t.$destroy()}},_i=class{};typeof HTMLElement=="function"&&(_i=class extends HTMLElement{$$ctor;$$s;$$c;$$cn=!1;$$d={};$$r=!1;$$p_d={};$$l={};$$l_u=new Map;$$me;$$shadowRoot=null;constructor(e,t,n){super(),this.$$ctor=e,this.$$s=t,n&&(this.$$shadowRoot=this.attachShadow(n))}addEventListener(e,t,n){if(this.$$l[e]=this.$$l[e]||[],this.$$l[e].push(t),this.$$c){let r=this.$$c.$on(e,t);this.$$l_u.set(t,r)}super.addEventListener(e,t,n)}removeEventListener(e,t,n){if(super.removeEventListener(e,t,n),this.$$c){let r=this.$$l_u.get(t);r&&(r(),this.$$l_u.delete(t))}}async connectedCallback(){if(this.$$cn=!0,!this.$$c){let t=function(a){return o=>{let l=Tr("slot");a!=="default"&&(l.name=a),D(o,l)}};var e=t;if(await Promise.resolve(),!this.$$cn||this.$$c)return;let n={},r=ns(this);for(let a of this.$$s)a in r&&(a==="default"&&!this.$$d.children?(this.$$d.children=t(a),n.default=!0):n[a]=t(a));for(let a of this.attributes){let o=this.$$g_p(a.name);o in this.$$d||(this.$$d[o]=xn(o,a.value,this.$$p_d,"toProp"))}for(let a in this.$$p_d)!(a in this.$$d)&&this[a]!==void 0&&(this.$$d[a]=this[a],delete this[a]);this.$$c=ts({component:this.$$ctor,target:this.$$shadowRoot||this,props:{...this.$$d,$$slots:n,$$host:this}}),this.$$me=vl(()=>{Dn(()=>{this.$$r=!0;for(let a of En(this.$$c)){if(!this.$$p_d[a]?.reflect)continue;this.$$d[a]=this.$$c[a];let o=xn(a,this.$$d[a],this.$$p_d,"toAttribute");o==null?this.removeAttribute(this.$$p_d[a].attribute||a):this.setAttribute(this.$$p_d[a].attribute||a,o)}this.$$r=!1})});for(let a in this.$$l)for(let o of this.$$l[a]){let l=this.$$c.$on(a,o);this.$$l_u.set(o,l)}this.$$l={}}}attributeChangedCallback(e,t,n){this.$$r||(e=this.$$g_p(e),this.$$d[e]=xn(e,n,this.$$p_d,"toProp"),this.$$c?.$set({[e]:this.$$d[e]}))}disconnectedCallback(){this.$$cn=!1,Promise.resolve().then(()=>{!this.$$cn&&this.$$c&&(this.$$c.$destroy(),this.$$me(),this.$$c=void 0)})}$$g_p(e){return En(this.$$p_d).find(t=>this.$$p_d[t].attribute===e||!this.$$p_d[t].attribute&&t.toLowerCase()===e)||e}});function xn(e,t,n,r){let a=n[e]?.type;if(t=a==="Boolean"&&typeof t!="boolean"?t!=null:t,!r||!n[e])return t;if(r==="toAttribute")switch(a){case"Object":case"Array":return t==null?null:JSON.stringify(t);case"Boolean":return t?"":null;case"Number":return t??null;default:return t}else switch(a){case"Object":case"Array":return t&&JSON.parse(t);case"Boolean":return t;case"Number":return t!=null?+t:t;default:return t}}function ns(e){let t={};return e.childNodes.forEach(n=>{t[n.slot||"default"]=!0}),t}function bt(e,t,n,r,a,o){let l=class extends _i{constructor(){super(e,n,a),this.$$p_d=t}static get observedAttributes(){return En(t).map(s=>(t[s].attribute||s).toLowerCase())}};return En(t).forEach(s=>{rn(l.prototype,s,{get(){return this.$$c&&s in this.$$c?this.$$c[s]:this.$$d[s]},set(c){c=xn(s,c,t),this.$$d[s]=c;var u=this.$$c;if(u){var d=Mt(u,s)?.get;d?u[s]=c:u.$set({[s]:c})}}})}),r.forEach(s=>{rn(l.prototype,s,{get(){return this.$$c?.[s]}})}),e.element=l,l}var rs=Z('<div class="altcha-checkbox"><input/> <svg aria-hidden="true" width="12" height="9" viewBox="0 0 12 9"><polyline points="1 5 4 8 11 1"></polyline></svg> <div class="altcha-spinner altcha-checkbox-spinner" aria-hidden="true"></div></div>');function ki(e,t){ot(t,!0);let n=J(t,"loading"),r=Vn(t,["$$slots","$$events","$$legacy","$$host","loading"]),a;function o(){a?.click()}var l={get loading(){return n()},set loading(d){n(d),Y()}},s=rs(),c=Q(s);Un(c,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),pt(c,d=>a=d,()=>a);var u=W(c,2);return Cr(2),q(s),pe(()=>j(s,"data-loading",n())),Mn("click",u,o),D(e,s),lt(l)}Nn(["click"]);bt(ki,{loading:{}},[],[],{mode:"open"});var as=Z('<div class="altcha-checkbox-native"><input/> <div class="altcha-spinner altcha-checkbox-native-spinner"></div></div>');function xi(e,t){ot(t,!0);let n=J(t,"loading"),r=Vn(t,["$$slots","$$events","$$legacy","$$host","loading"]);var a={get loading(){return n()},set loading(s){n(s),Y()}},o=as(),l=Q(o);return Un(l,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),Cr(2),q(o),pe(()=>j(o,"data-loading",n())),D(e,o),lt(a)}bt(xi,{loading:{}},[],[],{mode:"open"});var is=Z('<div><a target="_blank" class="altcha-logo" aria-hidden="true" tabindex="-1"><svg width="22" height="22" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.33955 16.4279C5.88954 20.6586 12.1971 21.2105 16.4279 17.6604C18.4699 15.947 19.6548 13.5911 19.9352 11.1365L17.9886 10.4279C17.8738 12.5624 16.909 14.6459 15.1423 16.1284C11.7577 18.9684 6.71167 18.5269 3.87164 15.1423C1.03163 11.7577 1.4731 6.71166 4.8577 3.87164C8.24231 1.03162 13.2883 1.4731 16.1284 4.8577C16.9767 5.86872 17.5322 7.02798 17.804 8.2324L19.9522 9.01429C19.7622 7.07737 19.0059 5.17558 17.6604 3.57212C14.1104 -0.658624 7.80283 -1.21043 3.57212 2.33956C-0.658625 5.88958 -1.21046 12.1971 2.33955 16.4279Z" fill="currentColor"></path><path d="M3.57212 2.33956C1.65755 3.94607 0.496389 6.11731 0.12782 8.40523L2.04639 9.13961C2.26047 7.15832 3.21057 5.25375 4.8577 3.87164C8.24231 1.03162 13.2883 1.4731 16.1284 4.8577L13.8302 6.78606L19.9633 9.13364C19.7929 7.15555 19.0335 5.20847 17.6604 3.57212C14.1104 -0.658624 7.80283 -1.21043 3.57212 2.33956Z" fill="currentColor"></path><path d="M7 10H5C5 12.7614 7.23858 15 10 15C12.7614 15 15 12.7614 15 10H13C13 11.6569 11.6569 13 10 13C8.3431 13 7 11.6569 7 10Z" fill="currentColor"></path></svg></a></div>');function Fr(e,t){ot(t,!0);let n=J(t,"strings"),r="https://altcha.org";var a={get strings(){return n()},set strings(s){n(s),Y()}},o=is(),l=Q(o);return j(l,"href",r),q(o),pe(()=>j(l,"aria-label",n().ariaLinkLabel)),D(e,o),lt(a)}bt(Fr,{strings:{}},[],[],{mode:"open"});var os=Z('<div class="altcha-footer"><p></p> <!></div>');function wr(e,t){ot(t,!0);let n=J(t,"logo"),r=J(t,"strings");var a={get logo(){return n()},set logo(u){n(u),Y()},get strings(){return r()},set strings(u){r(u),Y()}},o=os(),l=Q(o);gi(l,()=>r().footer,!0),q(l);var s=W(l,2);{var c=u=>{Fr(u,{get strings(){return r()}})};se(s,u=>{n()&&u(c)})}return q(o),D(e,o),lt(a)}bt(wr,{logo:{},strings:{}},[],[],{mode:"open"});var ls=Z('<div class="altcha-switch"><input/>  <div class="altcha-switch-toggle"><div class="altcha-spinner altcha-switch-spinner"></div></div></div>');function Ei(e,t){ot(t,!0);let n=J(t,"loading"),r=Vn(t,["$$slots","$$events","$$legacy","$$host","loading"]),a;function o(){a?.click()}var l={get loading(){return n()},set loading(d){n(d),Y()}},s=ls(),c=Q(s);Un(c,()=>({type:"checkbox",...r}),void 0,void 0,void 0,void 0,!0),pt(c,d=>a=d,()=>a);var u=W(c,2);return q(s),pe(()=>j(s,"data-loading",n())),Mn("click",u,o),D(e,s),lt(l)}Nn(["click"]);bt(Ei,{loading:{}},[],[],{mode:"open"});var fe=(e=>(e.ERROR="error",e.LOADING="loading",e.PLAYING="playing",e.PAUSED="paused",e.READY="ready",e))(fe||{}),F=(e=>(e.CODE="code",e.ERROR="error",e.VERIFIED="verified",e.VERIFYING="verifying",e.UNVERIFIED="unverified",e.EXPIRED="expired",e))(F||{}),ss=Z('<div class="altcha-code-challenge-title"> </div>'),cs=Z('<div class="altcha-spinner"></div>'),us=Mr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.8659 3.00017L22.3922 19.5002C22.6684 19.9785 22.5045 20.5901 22.0262 20.8662C21.8742 20.954 21.7017 21.0002 21.5262 21.0002H2.47363C1.92135 21.0002 1.47363 20.5525 1.47363 20.0002C1.47363 19.8246 1.51984 19.6522 1.60761 19.5002L11.1339 3.00017C11.41 2.52187 12.0216 2.358 12.4999 2.63414C12.6519 2.72191 12.7782 2.84815 12.8659 3.00017ZM10.9999 16.0002V18.0002H12.9999V16.0002H10.9999ZM10.9999 9.00017V14.0002H12.9999V9.00017H10.9999Z"></path></svg>'),fs=Mr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M15 7C15 6.44772 15.4477 6 16 6C16.5523 6 17 6.44772 17 7V17C17 17.5523 16.5523 18 16 18C15.4477 18 15 17.5523 15 17V7ZM7 7C7 6.44772 7.44772 6 8 6C8.55228 6 9 6.44772 9 7V17C9 17.5523 8.55228 18 8 18C7.44772 18 7 17.5523 7 17V7Z"></path></svg>'),ds=Mr('<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 12H7C8.10457 12 9 12.8954 9 14V19C9 20.1046 8.10457 21 7 21H4C2.89543 21 2 20.1046 2 19V12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12V19C22 20.1046 21.1046 21 20 21H17C15.8954 21 15 20.1046 15 19V14C15 12.8954 15.8954 12 17 12H20C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12Z"></path></svg>'),hs=Z('<button type="button" class="altcha-button altcha-button-secondary"><!></button>'),vs=Z('<audio hidden="" autoplay=""></audio>'),ps=Z('<div class="altcha-code-challenge"><form data-code-challenge="true"><!> <div class="altcha-code-challenge-text"> </div> <img class="altcha-code-challenge-image" alt=""/> <div class="altcha-code-challenge-row"><input type="text" class="altcha-input" autocomplete="off" name="" required=""/> <!> <button type="button" class="altcha-button altcha-button-secondary"><svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2V4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 9.25022 5.38734 6.82447 7.50024 5.38451L7.5 8H9.5V2L3.5 2V4L5.99918 3.99989C3.57075 5.82434 2 8.72873 2 12Z"></path></svg></button></div> <div class="altcha-code-challenge-buttons"><button type="submit" class="altcha-button"> </button> <button type="button" class="altcha-button altcha-button-secondary"> </button></div></form> <!></div>');function Ci(e,t){ot(t,!0);let n=J(t,"audioUrl"),r=J(t,"codeChallenge"),a=J(t,"config"),o=J(t,"imageUrl"),l=J(t,"onCancel"),s=J(t,"onReload"),c=J(t,"onSubmit"),u=J(t,"strings"),d=O(void 0),v=O(void 0),h=O(void 0),b=O(!1),g=O(""),E=O(!1);Nr(()=>(a().disableAutoFocus||xt().then(()=>{i(h)?.focus()}),()=>{i(v)&&(i(v).pause(),_(v,void 0))}));function $(){_(d,fe.PAUSED,!0)}function P(y){_(d,fe.ERROR,!0)}function oe(){_(d,fe.READY,!0)}function re(){_(d,fe.LOADING,!0)}function le(){_(d,fe.PLAYING,!0)}function H(){_(d,fe.PAUSED,!0)}function Ke(y){y.code==="Space"?(y.preventDefault(),y.stopPropagation(),L()):y.code==="Escape"&&(y.preventDefault(),y.stopPropagation(),l()?.())}function x(y){y.preventDefault(),y.stopPropagation(),c()?.(i(g))}function L(){i(v)?i(d)===fe.LOADING||(i(v).paused?(n()&&i(v).src!==n()&&(i(v).src=n()),i(v).currentTime=0,i(v).play()):i(v).pause()):(_(E,!0),requestAnimationFrame(()=>{i(v)&&n()&&(i(v).src=n(),i(v).play())}))}var ee={get audioUrl(){return n()},set audioUrl(y){n(y),Y()},get codeChallenge(){return r()},set codeChallenge(y){r(y),Y()},get config(){return a()},set config(y){a(y),Y()},get imageUrl(){return o()},set imageUrl(y){o(y),Y()},get onCancel(){return l()},set onCancel(y){l(y),Y()},get onReload(){return s()},set onReload(y){s(y),Y()},get onSubmit(){return c()},set onSubmit(y){c(y),Y()},get strings(){return u()},set strings(y){u(y),Y()}},V=ps(),N=Q(V),Ne=Q(N);{var ge=y=>{var te=ss(),wt=Q(te,!0);q(te),pe(()=>We(wt,u().verificationRequired)),D(y,te)};se(Ne,y=>{a().codeChallengeDisplay!=="standard"&&y(ge)})}var ye=W(Ne,2),X=Q(ye,!0);q(ye);var Ye=W(ye,2),C=W(Ye,2),B=Q(C);Ur(B),B.disabled=i(b),pt(B,y=>_(h,y),()=>i(h));var xe=W(B,2);{var m=y=>{var te=hs(),wt=Q(te);{var zn=we=>{var Ge=cs();D(we,Ge)},Gt=we=>{var Ge=us();D(we,Ge)},jn=we=>{var Ge=fs();D(we,Ge)},Hn=we=>{var Ge=ds();D(we,Ge)};se(wt,we=>{i(d)===fe.LOADING?we(zn):i(d)===fe.ERROR?we(Gt,1):i(d)===fe.PLAYING?we(jn,2):we(Hn,-1)})}q(te),pe(()=>{j(te,"title",u().getAudioChallenge),te.disabled=i(d)===fe.LOADING||i(d)===fe.ERROR,j(te,"aria-label",i(d)===fe.LOADING?u().loading:u().getAudioChallenge)}),ce("click",te,()=>L(),!0),D(y,te)};se(xe,y=>{r().audio&&y(m)})}var mt=W(xe,2);q(C);var vn=W(C,2),Ae=Q(vn),Fn=Q(Ae,!0);q(Ae);var yt=W(Ae,2),Bt=Q(yt,!0);q(yt),q(vn),q(N);var Kt=W(N,2);{var Yt=y=>{var te=vs();pt(te,wt=>_(v,wt),()=>i(v)),ce("error",te,P),ce("loadstart",te,re),ce("canplay",te,oe),ce("pause",te,H),ce("playing",te,le),ce("ended",te,$),D(y,te)};se(Kt,y=>{i(E)&&y(Yt)})}return q(V),pe(()=>{We(X,u().enterCodeFromImage),j(Ye,"src",o()),j(B,"minlength",r().length||1),j(B,"maxlength",r().length),j(B,"placeholder",u().enterCode),j(B,"aria-label",i(d)===fe.LOADING?u().loading:i(d)===fe.PLAYING?"":u().enterCodeAria),j(B,"aria-live",i(d)?"assertive":"polite"),j(B,"aria-busy",i(d)===fe.LOADING),j(mt,"title",u().reload),j(mt,"aria-label",u().reload),j(Ae,"aria-label",u().verify),We(Fn,u().verify),j(yt,"aria-label",u().cancel),We(Bt,u().cancel)}),ce("submit",N,x,!0),Mn("keydown",B,Ke),Ql(B,()=>i(g),y=>_(g,y)),ce("click",mt,()=>s()?.(),!0),ce("click",yt,()=>l()?.(),!0),D(e,V),lt(ee)}Nn(["keydown"]);bt(Ci,{audioUrl:{},codeChallenge:{},config:{},imageUrl:{},onCancel:{},onReload:{},onSubmit:{},strings:{}},[],[],{mode:"open"});var gs=Z('<div class="altcha-popover-backdrop" data-backdrop=""></div>'),bs=Z('<div class="altcha-popover-arrow"></div>'),ms=Z('<div role="button" class="altcha-popover-close">&times;</div>'),ys=Z('<!> <div><!> <!> <div class="altcha-popover-content"><!></div></div>',1);function _r(e,t){ot(t,!0);let n=J(t,"anchor"),r=J(t,"children"),a=J(t,"display",7,"standard"),o=J(t,"backdrop",7,!1),l=J(t,"onClickOutside"),s=J(t,"onClickOutsideDelay",7,600),c=J(t,"onClose"),u=J(t,"placement",7,"auto"),d=J(t,"updateUISignal"),v=J(t,"variant",7,"neutral"),h=Vn(t,["$$slots","$$events","$$legacy","$$host","anchor","children","display","backdrop","onClickOutside","onClickOutsideDelay","onClose","placement","updateUISignal","variant"]),b=O(void 0),g=O(void 0),E=O(!1),$=O(0);Se(()=>{u()!=="auto"&&_(E,u()==="top")}),Se(()=>{d()&&H()}),Nr(()=>{let C=a()==="bottomsheet"||a()==="overlay";return C&&(i(g)&&document.body.append(i(g)),i(b)&&document.body.append(i(b))),H(),xt().then(()=>{_($,Date.now(),!0)}),()=>{C&&(i(g)&&document.body.removeChild(i(g)),i(b)&&document.body.removeChild(i(b)))}});function P(){c()?.()}function oe(C){let B=C.target;!i(b)?.contains(B)&&(!s()||i($)+s()<Date.now())&&l()?.()}function re(){H()}function le(){H()}function H(){if(n()&&u()==="auto"&&i(b)){let C=n().getBoundingClientRect(),xe=document.documentElement.clientHeight-(C.top+C.height)<i(b).clientHeight;i(E)!==xe&&_(E,xe)}}var Ke={get anchor(){return n()},set anchor(C){n(C),Y()},get children(){return r()},set children(C){r(C),Y()},get display(){return a()},set display(C="standard"){a(C),Y()},get backdrop(){return o()},set backdrop(C=!1){o(C),Y()},get onClickOutside(){return l()},set onClickOutside(C){l(C),Y()},get onClickOutsideDelay(){return s()},set onClickOutsideDelay(C=600){s(C),Y()},get onClose(){return c()},set onClose(C){c(C),Y()},get placement(){return u()},set placement(C="auto"){u(C),Y()},get updateUISignal(){return d()},set updateUISignal(C){d(C),Y()},get variant(){return v()},set variant(C="neutral"){v(C),Y()}},x=ys();ce("click",Et,oe,!0),ce("resize",Et,re),ce("scroll",Et,le);var L=Lt(x);{var ee=C=>{var B=gs();pt(B,xe=>_(g,xe),()=>i(g)),D(C,B)};se(L,C=>{o()&&C(ee)})}var V=W(L,2);Un(V,()=>({...h,class:`altcha-popover ${(t.class||"")??""}`,"data-popover":!0,"data-variant":v(),"data-top":i(E),"data-display":a()}));var N=Q(V);{var Ne=C=>{var B=bs();D(C,B)};se(N,C=>{a()==="standard"&&C(Ne)})}var ge=W(N,2);{var ye=C=>{var B=ms();ce("click",B,P,!0),D(C,B)};se(ge,C=>{a()!=="standard"&&C(ye)})}var X=W(ge,2),Ye=Q(X);return Pl(Ye,()=>r()??ft),q(X),q(V),pt(V,C=>_(b,C),()=>i(b)),D(e,x),lt(Ke)}bt(_r,{anchor:{},children:{},display:{},backdrop:{},onClickOutside:{},onClickOutsideDelay:{},onClose:{},placement:{},updateUISignal:{},variant:{}},[],[],{mode:"open"});function ws(e){return Array.from(new Uint8Array(e)).map(t=>t.toString(16).padStart(2,"0")).join("")}function _s(e,t="altcha-css",n){if(typeof document<"u"&&document&&!document.getElementById(t)){let r=document.createElement("style");r.id=t,r.textContent=e;let a=document.currentScript?.nonce??document.querySelector('meta[name="csp-nonce"]')?.content;a&&(r.nonce=a),document.head.appendChild(r)}}async function Si(e){let{challenge:t,concurrency:n=navigator.hardwareConcurrency,controller:r=new AbortController,createWorker:a,onOutOfMemory:o=h=>h>1?Math.floor(h/2):0,counterMode:l,timeout:s=9e4}=e,c=Math.min(16,Math.max(1,n)),u=[],d=()=>{for(let h of u)h.terminate()};for(let h=0;h<c;h++)u.push(await a(t.parameters.algorithm));let v=null;try{v=await Promise.race(u.map((h,b)=>(r.signal.addEventListener("abort",()=>{h.postMessage({type:"abort"})}),new Promise((g,E)=>{h.addEventListener("error",$=>{E($)}),h.addEventListener("message",$=>{if($.data){for(let P of u)P!==h&&P.postMessage({type:"abort"});if($.data.error)return E(new Error($.data.error))}g($.data)}),h.postMessage({challenge:t,counterMode:l,counterStart:b,counterStep:c,timeout:s,type:"work"})}))))}catch(h){if(h instanceof Error&&!!h?.message?.includes("Out of memory")&&o){d();let g=o(c);if(g)return Si({...e,challenge:t,controller:r,concurrency:g,createWorker:a})}throw h}finally{d()}return r.signal.aborted?null:v||null}var kr=class{TAG_CODES={INPUT:1,TEXTAREA:2,SELECT:3,BUTTON:4,A:5,DETAILS:6,SUMMARY:7,IFRAME:8,VIDEO:9,AUDIO:10};maxSamples;sampleInterval;target;focusStartTime=0;focusInteraction=0;focusInteractionTimer=null;lastPointerSample=0;lastTouchSample=0;lastScrollSample=0;pendingPointer=null;pendingTouch=null;focus=[];pointer=[];scroll=[];touch=[];constructor(t={}){let{maxSamples:n=60,sampleInterval:r=50,target:a=window}=t;this.maxSamples=n,this.sampleInterval=r,this.target=a,this.attach()}destroy(){let t={capture:!0};this.target.removeEventListener("focusin",this.onFocus,t),this.target.removeEventListener("keydown",this.onInteraction,t),this.target.removeEventListener("pointerdown",this.onInteraction,t),this.target.removeEventListener("pointermove",this.onPointer,t),this.target.removeEventListener("scroll",this.onScroll,t),this.target.removeEventListener("touchmove",this.onTouchMove,t)}export(){return{focus:this.focus,maxTouchPoints:navigator.maxTouchPoints||0,pointer:this.pointer,scroll:this.scroll,time:Date.now(),touch:this.touch}}attach(){let t={passive:!0,capture:!0};this.target.addEventListener("focusin",this.onFocus,t),this.target.addEventListener("keydown",this.onInteraction,t),this.target.addEventListener("pointerdown",this.onInteraction,t),this.target.addEventListener("pointermove",this.onPointer,t),this.target.addEventListener("scroll",this.onScroll,t),this.target.addEventListener("touchmove",this.onTouchMove,t)}evict(t){t.length>this.maxSamples&&t.splice(0,t.length-this.maxSamples)}onFocus=t=>{if(this.focusInteraction===2)return;let n=t.target;if(!(n instanceof Element))return;let r=performance.now();this.focusStartTime===0&&(this.focusStartTime=r),this.focus.push([Math.round(r-this.focusStartTime),n.tabIndex,this.TAG_CODES[n.tagName]??0,this.focusInteraction?1:0]),this.evict(this.focus)};onInteraction=t=>{this.focusInteraction="keyCode"in t?1:2,this.focusInteractionTimer&&clearTimeout(this.focusInteractionTimer),this.focusInteractionTimer=setTimeout(()=>{this.focusInteraction=0},100)};onPointer=t=>{if(t.pointerType==="touch")return;let n=t.timeStamp||performance.now();this.pendingPointer=[Math.round(t.clientX),Math.round(t.clientY),Math.round(n)],n-this.lastPointerSample>=this.sampleInterval&&(this.pointer.push(this.pendingPointer),this.lastPointerSample=n,this.pendingPointer=null,this.evict(this.pointer))};onScroll=()=>{let t=performance.now();t-this.lastScrollSample<this.sampleInterval||(this.scroll.push([Math.round(window.scrollY),Math.round(t)]),this.lastScrollSample=t,this.evict(this.scroll))};onTouchMove=t=>{let n=t.timeStamp||performance.now(),r=t.touches[0];r&&(this.pendingTouch=[Math.round(r.clientX),Math.round(r.clientY),Math.round(n),Math.round(r.force*1e3)/1e3,Math.round(r.radiusX||0),Math.round(r.radiusY||0)],n-this.lastTouchSample>=this.sampleInterval&&(this.touch.push(this.pendingTouch),this.lastTouchSample=n,this.pendingTouch=null,this.evict(this.touch)))}},ks=Z('<div class="altcha-overlay-backdrop" data-backdrop=""></div>'),xs=Z('<div class="altcha-overlay-content"></div>'),Es=Z('<div role="button" class="altcha-overlay-close">&times;</div> <!>',1),Cs=Z('<div class="altcha-floating-arrow"></div>'),Ss=Z('<input type="hidden"/>'),Ts=Z('<div class="altcha-error">Secure context (HTTPS) required.</div>'),$s=Z('<div class="altcha-error"> </div>'),As=Z('<div class="altcha-error"> </div>'),Rs=Z("<!> <!>",1),Is=Z('<!> <div class="altcha"><!> <div class="altcha-main"><div><div class="altcha-checkbox-wrap"><!> <label><!></label></div> <!></div> <!> <!> <!></div> <!></div>',1);function Os(e,t){ot(t,!0);let n=()=>ia(d,"$altchaDefaults",a),r=()=>ia(g,"$altchaI18nStore",a),[a,o]=Zo(),l='input[type="text"]:not([data-no-spamfilter]), textarea:not([data-no-spamfilter])',s='input[type="submit"], button[type="submit"], button:not([type="button"]):not([type="reset"])',c=["ar","fa","he","ur"],{isSecureContext:u}=globalThis,{store:d}=globalThis.$altcha.defaults,v=navigator.hardwareConcurrency||2,h=navigator.deviceMemory||0,b=h&&h<=4?Math.min(4,v):v,g=globalThis.$altcha.i18n.store,E=t.$$host,$=(f,p)=>{xt().then(()=>{E?.dispatchEvent(new CustomEvent(f,{detail:p}))})},P=null,oe=O(nt(new URL(location.origin))),re=O(!1),le=O(null),H=O(null),Ke=O(null),x=O(nt(F.UNVERIFIED)),L=O(void 0),ee=O(void 0),V=O(null),N=O(void 0),Ne=O(null),ge=O(null),ye=O(null),X=O(null),Ye=O(nt([])),C=O(0),B=O(nt({})),xe=O(!0),m=be(()=>({fetch:(f,p)=>fetch(f,p),audioChallengeLanguage:"",auto:"off",barPlacement:"bottom",challenge:"",codeChallenge:null,codeChallengeDisplay:"standard",credentials:null,debug:!1,disableAutoFocus:!1,display:"standard",floatingAnchor:"",floatingOffset:8,floatingPersist:!1,floatingPlacement:"auto",hideFooter:!1,hideLogo:!1,humanInteractionSignature:!0,language:"",mockError:!1,minDuration:500,overlayContent:"",name:"altcha",popoverPlacement:"auto",retryOnOutOfMemoryError:!0,setCookie:null,serverVerificationFields:!1,serverVerificationTimeZone:!1,test:!1,timeout:9e4,type:"checkbox",validationMessage:"",verifyFunction:null,verifyUrl:"",workers:b,...n(),...i(B)})),mt=be(()=>`altcha-checkbox-${t.id||Math.floor(Math.random()*1e12).toString(16)}`),vn=be(()=>Ii(i(m).type)),Ae=be(()=>i(m).auto),Fn=be(()=>i(x)===F.VERIFYING),yt=be(()=>!i(m).hideFooter),Bt=be(()=>!i(m).hideLogo&&i(m).display!=="bar"),Kt=be(()=>Oi(r(),[i(m).language,document.documentElement.lang,...navigator.languages])),Yt=be(()=>c.includes(i(Kt).language)?"rtl":void 0),y=be(()=>({...i(Kt).strings})),te=be(()=>i(le)?.audio?.match(/^(https?:)?\//)?pn(i(le).audio,i(oe),{language:i(m).audioChallengeLanguage||i(Kt).language}).toString():i(le)?.audio),wt=be(()=>i(le)?.image?.match(/^(https?:)?\//)?pn(i(le).image,i(oe)):i(le)?.image);Se(()=>{qt({auto:t.auto,challenge:t.challenge,display:t.display,language:t.language,name:t.name,type:t.type,workers:t.workers})}),Se(()=>{if(t.configuration)try{qt(JSON.parse(t.configuration))}catch{K("unable to parse the `configuration` attribute (JSON expected)")}}),Se(()=>{i(Ke)!==i(m).display&&gn(i(m).display)}),Se(()=>{i(re)&&i(x)===F.VERIFYING&&_(re,!1)}),Se(()=>{!i(re)&&i(x)===F.VERIFIED&&_(re,!0)}),Se(()=>{if(!i(re)){let f=Bn();f&&f.checked&&(f.checked=!1)}}),Se(()=>{i(x)===F.VERIFIED&&Bn()?.setCustomValidity("")}),Se(()=>{if(i(Ae)==="onload"){let f=setTimeout(()=>{It()},1);return()=>{f&&clearTimeout(f)}}}),Se(()=>{i(ge)&&K("error:",i(ge))}),Se(()=>{i(X)&&i(m).setCookie&&Yi(i(X),i(m).setCookie)}),Nr(()=>(K("mounted","3.1.0"),E&&globalThis.$altcha.instances.add(E),_(V,i(N)?.closest("form"),!0),i(V)?.addEventListener("reset",Kr),i(V)?.addEventListener("submit",Yr,{capture:!0}),i(V)?.addEventListener("focusin",Br),zn(),i(m).humanInteractionSignature&&(K("human interaction signature enabled"),P=new kr),$("load"),u||K("secure context (HTTPS) required"),()=>{jn(),E&&globalThis.$altcha.instances.delete(E),i(ye)&&clearTimeout(i(ye)),i(V)?.removeEventListener("reset",Kr),i(V)?.removeEventListener("submit",Yr,{capture:!0}),i(V)?.removeEventListener("focusin",Br),P?.destroy()}));function zn(){_(Ye,[...globalThis.$altcha.plugins].map(f=>new f(E)),!0),K("activating plugins",i(Ye).map(f=>f.constructor.name));for(let f of i(Ye))f.activate()}async function Gt(f,...p){let w;for(let k of i(Ye))w=await k[f].call(k,...p);return w}function jn(){for(let f of i(Ye))f.destroy()}function Hn(f){let[p,w]=f.salt.split("?"),k={};if(w)try{Object.assign(k,Object.fromEntries(new URLSearchParams(w).entries()))}catch{}let T={codeChallenge:f.codeChallenge,parameters:{algorithm:f.algorithm,cost:1,data:k,expiresAt:k?.expires?parseInt(k.expires,10):void 0,keyLength:f.algorithm==="SHA-512"?64:f.algorithm==="SHA-384"?48:32,nonce:ws(new TextEncoder().encode(f.salt)),keyPrefix:f.challenge,salt:""},signature:f.signature};return Object.defineProperties(T,{_originalSalt:{enumerable:!1,value:f.salt,writable:!1},_version:{enumerable:!1,value:1,writable:!1}}),T}function we(f,p){return{algorithm:f.parameters.algorithm,challenge:f.parameters.keyPrefix,number:p.counter,salt:"_originalSalt"in f?f._originalSalt:f.parameters.nonce,signature:f.signature,took:p.time||0}}async function Ge(f){await new Promise(p=>setTimeout(p,f))}async function Hr(f=i(m).challenge,p){let w=await Gt("onFetchChallenge",f),k=null;if(w!==void 0)return w;if(typeof f=="string")if(f.startsWith("{")){K("parsing JSON challenge");try{k=JSON.parse(f)}catch{throw new Error("Unable to parse JSON challenge.")}}else{K("fetching challenge from",p?.method||"GET",f),_(oe,new URL(f,location.origin),!0);let T=await i(m).fetch(f,{credentials:i(m).credentials||void 0,...p});await qr(T);let S=T.headers.get("x-altcha-config");S&&Hi(S);let z=await T.json();if(z&&"his"in z&&z.his){if(K("requested HIS"),!P)throw new Error("Server requested HIS data but collector is disabled.");return Hr(pn(z.his.url,i(oe)),{body:JSON.stringify({his:P.export()}),headers:{"content-type":"application/json"},method:"POST"})}z&&"hisResult"in z&&z.hisResult&&K("HIS result",z.hisResult),k=z}else if(f&&typeof f=="object")try{k=JSON.parse(JSON.stringify(f))}catch{throw new Error("Unable to parse JSON challenge.")}if(Ai(k)&&(k=Hn(k)),!Ri(k))throw new Error("Challenge validation failed.");return k}function Ai(f){return typeof f=="object"&&"challenge"in f}function Ri(f){return!!f&&typeof f=="object"&&"parameters"in f&&!!f.parameters&&typeof f.parameters=="object"&&"algorithm"in f.parameters&&"nonce"in f.parameters&&"salt"in f.parameters&&"keyPrefix"in f.parameters}function Bn(){return document.getElementById(i(mt))}function Ii(f){switch(f){case"checkbox":return ki;case"switch":return Ei;default:return xi}}function Oi(f,p){let w=Object.keys(f).map(T=>T.toLowerCase()),k=p.reduce((T,S)=>(S=S.toLowerCase(),T||(f[S]?S:null)||w.find(z=>S.split("-")[0]===z.split("-")[0])||null),null);return f[k||""]||(k="en"),{language:k,strings:f[k]}}function Pi(f){switch(f){case"bar":return i(m).barPlacement||"bottom";case"floating":return i(m).floatingPlacement||"auto";default:return}}function Li(f){return[...i(V)?.querySelectorAll(l)||[]].reduce((w,k)=>{let T=k.name,S=k.value;return T&&S&&(w[T]=/\n/.test(S)?S.replace(new RegExp("(?<!\\r)\\n","g"),`\r
`):S),w},{})}function Di(){try{return Intl.DateTimeFormat().resolvedOptions().timeZone}catch{}}function pn(f,p,w){let k=new URL(f,p);if(k.search||(k.search=p.search),w)for(let T in w)w[T]!==void 0&&w[T]!==null&&k.searchParams.set(T,w[T]);return k.toString()}function Mi(f){!i(re)&&f.currentTarget.checked?(f.preventDefault(),f.currentTarget.checked=!1,i(x)!==F.VERIFYING&&It()):f.currentTarget.checked||(f.preventDefault(),Re())}function Ni(f){i(x)===F.VERIFYING?f.currentTarget.setCustomValidity(i(y).waitAlert):i(m).validationMessage&&f.currentTarget.setCustomValidity(i(m).validationMessage)}function Ui(){gn(i(m).display),Re()}function Vi(){bn()}function Fi(f){let p=f.target;i(m).display==="floating"&&p&&!E?.contains(p)&&!p.hasAttribute("data-backdrop")&&!p.closest("[data-popover]")&&i(x)!==F.VERIFIED&&!i(m).floatingPersist&&Kn()}function Br(f){i(Ae)==="onfocus"&&i(x)===F.UNVERIFIED&&It()}function Kr(){gn(i(m).display),Re()}function Yr(f){f.target?.getAttribute("data-code-challenge")!=="true"&&i(Ae)==="onsubmit"&&i(x)===F.UNVERIFIED&&(f.preventDefault(),f.stopPropagation(),_(Ne,f.submitter,!0),Yn(),It().then(w=>{w&&!i(le)&&xt().then(()=>{Gr(i(Ne))})}))}function zi(f){f.persisted&&(gn(i(m).display),Re())}function ji(){bn()}function Hi(f){try{let p=JSON.parse(f);p&&typeof p=="object"&&qt({serverVerificationFields:p?.sentinel?.fields,serverVerificationTimeZone:p?.sentinel?.timeZone,verifyUrl:p.verifyurl,...p})}catch(p){K("unable to configure from x-altcha-config header",p)}}function Bi(f=20){if(!i(N))return;let p=i(m).floatingPlacement;if(!i(ee)&&(_(ee,(i(m).floatingAnchor instanceof HTMLElement?i(m).floatingAnchor:i(m).floatingAnchor?document.querySelector(i(m).floatingAnchor):i(V)?.querySelector(s))||i(V),!0),!i(ee))){K("unable to find floating anchor element");return}let w=parseInt(i(m).floatingOffset,10)||12,k=i(ee).getBoundingClientRect(),T=i(N).getBoundingClientRect(),S=document.documentElement.clientHeight,z=document.documentElement.clientWidth,Ee=!p||p==="auto"?k.bottom+T.height+w+f>S:p==="top",G=Math.max(f,Math.min(z-f-T.width,k.left+k.width/2-T.width/2));if(i(N).style.setProperty("--altcha-floating-left",`${G}px`),i(N).style.setProperty("--altcha-floating-top",Ee?`${k.top-(T.height+w)}px`:`${k.bottom+w}px`),i(N).setAttribute("data-floating-position",Ee?"top":"bottom"),i(L)){let ae=i(L).getBoundingClientRect();i(L).style.left=k.left-G+k.width/2-ae.width/2+"px"}}async function Ki(f,p){let w=await Gt("onRequestServerVerification",f,p);if(w!==void 0)return w;if(K("requesting server verification from",i(m).verifyUrl),!i(m).verifyUrl)throw new Error("Parameter verifyUrl must be set for server verification.");let k=await i(m).fetch(pn(i(m).verifyUrl,i(oe)),{body:JSON.stringify({code:p,fields:i(m).serverVerificationFields?Li():void 0,payload:f,timeZone:i(m).serverVerificationTimeZone?Di():void 0}),credentials:i(m).credentials||void 0,headers:{"Content-Type":"application/json"},method:"POST"});await qr(k);let T=await k.json();return T&&typeof T=="object"&&"payload"in T&&T.payload&&$("serververification",T),T}function Gr(f){i(V)&&"requestSubmit"in i(V)?i(V).requestSubmit(f):i(V)?.reportValidity()&&(f?f.click():i(V).submit())}function Yi(f,p={}){let{domain:w,name:k=i(m).name,maxAge:T,path:S,sameSite:z,secure:Ee}=p,G=`${encodeURIComponent(k)}=${encodeURIComponent(f)}`;w&&(G+=`; Domain=${w}`),T!=null&&(G+=`; Max-Age=${T}`),S&&(G+=`; Path=${S}`),z&&(G+=`; SameSite=${z}`),Ee&&(G+="; Secure"),document.cookie=G}function gn(f){switch(f){case"bar":case"floating":case"overlay":Kn(),(!i(Ae)||i(Ae)==="off")&&(i(B).auto="onsubmit");break;case"standard":Yn()}i(Ke)!==f&&_(Ke,f,!0)}function Gi(f){i(ye)&&clearTimeout(i(ye));let p=()=>{i(x)!==F.UNVERIFIED?(_(re,!1),Ie(F.EXPIRED)):Re(),$("expired")},w=f*1e3-Date.now();w>=1?_(ye,setTimeout(p,w),!0):p()}async function qr(f){if(f.status>=400){if(f.headers.get("content-type")?.includes("/json")){let w;try{w=await f.json()}catch{}if(w&&"error"in w)throw new Error(`Server responded with ${f.status} - ${w.error}`)}throw new Error(`Server responded with ${f.status}.`)}let p=f.headers.get("content-type");if(!p||!p.includes("/json"))throw new Error(`Server responded with invalid content-type. Expected application/json, received ${p}.`)}async function Wr(f){if(!i(X)){Ie(F.ERROR,"Cannot verify code challenge without PoW payload.");return}Ie(F.VERIFYING);let p=null;if(i(m).verifyUrl)p=await Ki(i(X),f);else if(i(m).verifyFunction)p=await i(m).verifyFunction(i(X),f);else{Ie(F.ERROR,"Parameter verifyUrl is required for code challenge verification.");return}p?.payload&&(_(X,p.payload,!0),K("server payload",i(X))),p?.verified===!0?(K("verified"),Ie(F.VERIFIED),$("verified",{payload:i(X)}),i(Ae)==="onsubmit"&&xt().then(()=>{Gr(i(Ne))})):Ie(F.ERROR,p?.reason||"Verification failed."),i(m).disableAutoFocus||Bn()?.focus()}function qt(f){Object.assign(i(B),{...Object.fromEntries(Object.entries(f).filter(([p,w])=>w!==void 0))})}function qi(){return{...i(m)}}function Wi(){return i(x)}function Kn(){_(xe,!1)}function K(...f){(i(m).debug||f.some(p=>p instanceof Error))&&console[f[0]instanceof Error?"error":"log"]("ALTCHA",`[name=${i(m).name}]`,...f)}function Re(f=F.UNVERIFIED,p=null){_(re,!1),_(ge,p,!0),_(X,null),i(H)&&i(H).abort(),i(ye)&&(clearTimeout(i(ye)),_(ye,null)),Ie(f)}function Ie(f,p=null){_(x,f,!0),_(ge,p,!0),$("statechange",{payload:i(X),state:i(x)})}function Yn(){_(xe,!0),xt().then(()=>{bn()})}function bn(){if(i(m).display==="floating")return Bi();_(C,i(C)+1)}async function It(f={}){let{concurrency:p=Math.max(1,i(m).workers),controller:w=new AbortController,minDuration:k=i(m).minDuration}=f,T=performance.now(),S=null,z=null,Ee=!1,G=await Gt("onVerify",f);if(G!==void 0)return G;Re(F.VERIFYING),_(H,w,!0);try{if(!u)throw new Error("Secure context (HTTPS) required.");if(i(m).mockError)throw new Error("Mock error.");if(i(m).test)return K("running test mode with null challenge"),await Ge(Math.max(0,k-(performance.now()-T))),i(H)?.signal.aborted?(Re(),null):(_(X,btoa(JSON.stringify({challenge:null,solution:null,test:!0})),!0),K("verified"),Ie(F.VERIFIED),$("verified",{payload:i(X)}),{payload:i(X)});if(S=await Hr(),!S)throw new Error("Failed to fetch challenge.");K("challenge",S),"configuration"in S&&(K("re-configuring from challenge",S.configuration),qt(S.configuration)),S.parameters.expiresAt&&Gi(S.parameters.expiresAt),Ee="_version"in S&&S._version===1;let ae=globalThis.$altcha.algorithms.get(S.parameters.algorithm);if(!ae)throw new Error(`Unsupported algorithm ${S.parameters.algorithm}.`);if(z=await Si({challenge:S,concurrency:p,controller:w,createWorker:ae,counterMode:Ee?"string":"uint32",onOutOfMemory:st=>{if(K("out of memory error received"),$("outofmemory"),i(m).retryOnOutOfMemoryError&&st>1){let ct=Math.floor(st/2);return K(`retrying with ${ct} workers...`),ct}},timeout:i(m).timeout}),i(H)?.signal.aborted)return Re(),null;if(!z)throw new Error("Failed to find solution.");K("solution",z),await Ge(Math.max(0,k-(performance.now()-T))),_(le,S.codeChallenge||i(m).codeChallenge||null,!0),Ee?_(X,btoa(JSON.stringify(we(S,z))),!0):_(X,btoa(JSON.stringify({challenge:{parameters:S.parameters,signature:S.signature},solution:z})),!0),i(le)?(K("requesting code verification"),Ie(F.CODE),$("codechallenge",{codeChallenge:i(le)})):i(m).verifyUrl?await Wr():(K("verified"),Ie(F.VERIFIED),$("verified",{payload:i(X)}))}catch(ae){return K("verification failed",ae),Ie(F.ERROR,String(ae)),null}finally{_(H,null)}return{challenge:S,payload:i(X),solution:z}}var Ji={configure:qt,getConfiguration:qi,getState:Wi,hide:Kn,log:K,reset:Re,setState:Ie,show:Yn,updateUI:bn,verify:It},Jr=Is();ce("scroll",cr,Vi),ce("click",cr,Fi),ce("pageshow",Et,zi),ce("resize",Et,ji);var Zr=Lt(Jr);{var Zi=f=>{var p=ks();D(f,p)};se(Zr,f=>{i(m).display==="overlay"&&i(xe)&&f(Zi)})}var qe=W(Zr,2),Xr=Q(qe);{var Xi=f=>{var p=Es(),w=Lt(p),k=W(w,2);{var T=S=>{var z=xs();gi(z,()=>document.querySelector(i(m).overlayContent)?.innerHTML,!0),q(z),D(S,z)};se(k,S=>{i(m).overlayContent&&S(T)})}ce("click",w,Ui,!0),D(f,p)};se(Xr,f=>{i(m).display==="overlay"&&i(xe)&&f(Xi)})}var Gn=W(Xr,2),qn=Q(Gn),Wn=Q(qn),Qr=Q(Wn);{let f=be(()=>i(m).display==="standard"&&i(Ae)!=="onsubmit"||i(x)===F.VERIFYING);Ml(Qr,()=>i(vn),(p,w)=>{w(p,{get id(){return i(mt)},name:"",get required(){return i(f)},get loading(){return i(Fn)},get checked(){return i(re)},onchange:Mi,oninvalid:Ni})})}var Jn=W(Qr,2),Qi=Q(Jn);{var eo=f=>{var p=yn();pe(()=>We(p,i(y).verificationRequired)),D(f,p)},to=f=>{var p=yn();pe(()=>We(p,i(y).verifying)),D(f,p)},no=f=>{var p=yn();pe(()=>We(p,i(y).verified)),D(f,p)},ro=f=>{var p=yn();pe(()=>We(p,i(y).label)),D(f,p)};se(Qi,f=>{i(x)===F.CODE&&i(le)?f(eo):i(x)===F.VERIFYING?f(to,1):i(x)===F.VERIFIED?f(no,2):f(ro,-1)})}q(Jn),q(Wn);var ao=W(Wn,2);{var io=f=>{Fr(f,{get strings(){return i(y)}})};se(ao,f=>{i(Bt)&&f(io)})}q(qn);var ea=W(qn,2);{var oo=f=>{{let p=be(()=>i(m).display==="bar"&&i(Bt));wr(f,{get logo(){return i(p)},get strings(){return i(y)}})}};se(ea,f=>{i(yt)&&f(oo)})}var ta=W(ea,2);{var lo=f=>{var p=Cs();pt(p,w=>_(L,w),()=>i(L)),D(f,p)};se(ta,f=>{i(m).display==="floating"&&f(lo)})}var so=W(ta,2);{var co=f=>{var p=Ss();Ur(p),pe(()=>{j(p,"name",i(m).name),Jl(p,i(X))}),D(f,p)};se(so,f=>{i(m).setCookie||f(co)})}q(Gn);var uo=W(Gn,2);{var fo=f=>{_r(f,{get anchor(){return i(N)},onClickOutside:()=>{u&&Re()},get placement(){return i(m).popoverPlacement},role:"alert",variant:"error",get dir(){return i(Yt)},get updateUISignal(){return i(C)},children:(p,w)=>{var k=ha(),T=Lt(k);{var S=G=>{var ae=Ts();D(G,ae)},z=G=>{var ae=$s(),st=Q(ae,!0);q(ae),pe(()=>We(st,i(y).expired)),D(G,ae)},Ee=G=>{var ae=As(),st=Q(ae,!0);q(ae),pe(()=>{j(ae,"title",i(ge)),We(st,i(y).error)}),D(G,ae)};se(T,G=>{!i(ge)&&!u?G(S):!i(ge)&&i(x)===F.EXPIRED?G(z,1):G(Ee,-1)})}D(p,k)},$$slots:{default:!0}})},ho=f=>{var p=ha(),w=Lt(p);Dl(w,()=>i(le),k=>{{let T=be(()=>i(m).codeChallengeDisplay!=="standard");_r(k,{get anchor(){return i(N)},get backdrop(){return i(T)},get display(){return i(m).codeChallengeDisplay},onClose:()=>{Re()},get placement(){return i(m).popoverPlacement},role:"dialog",get"aria-label"(){return i(y).verificationRequired},get dir(){return i(Yt)},get updateUISignal(){return i(C)},children:(S,z)=>{var Ee=Rs(),G=Lt(Ee);Ci(G,{get audioUrl(){return i(te)},get imageUrl(){return i(wt)},onCancel:()=>Re(),onReload:()=>It(),onSubmit:ct=>Wr(ct),get codeChallenge(){return i(le)},get config(){return i(m)},get strings(){return i(y)}});var ae=W(G,2);{var st=ct=>{wr(ct,{get logo(){return i(Bt)},get strings(){return i(y)}})};se(ae,ct=>{i(yt)&&i(m).codeChallengeDisplay!=="standard"&&ct(st)})}D(S,Ee)},$$slots:{default:!0}})}}),D(f,p)};se(uo,f=>{i(ge)||i(x)===F.EXPIRED||!u?f(fo):i(le)&&i(x)===F.CODE&&f(ho,1)})}q(qe),pt(qe,f=>_(N,f),()=>i(N)),pe(f=>{j(qe,"data-state",i(x)),j(qe,"data-display",i(m).display||void 0),j(qe,"data-placement",f),j(qe,"data-visible",i(xe)||void 0),j(qe,"dir",i(Yt)),j(Jn,"for",i(mt)),qe.dir=qe.dir},[()=>Pi(i(m).display)]),D(e,Jr);var vo=lt(Ji);return o(),vo}typeof window<"u"&&window.customElements&&!customElements.get("altcha-widget")&&customElements.define("altcha-widget",bt(Os,{auto:{type:"String"},challenge:{type:"String"},configuration:{type:"String"},display:{type:"String"},language:{type:"String"},name:{type:"String"},theme:{type:"String"},type:{type:"String"},workers:{type:"Number"}},[],["configure","getConfiguration","getState","hide","log","reset","setState","show","updateUI","verify"]));var Ti=`(function() {
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
`,ma=typeof self<"u"&&self.Blob&&new Blob(["(self.URL || self.webkitURL).revokeObjectURL(self.location.href);",Ti],{type:"text/javascript;charset=utf-8"});function zr(e){let t;try{if(t=ma&&(self.URL||self.webkitURL).createObjectURL(ma),!t)throw"";let n=new Worker(t,{name:e?.name});return n.addEventListener("error",()=>{(self.URL||self.webkitURL).revokeObjectURL(t)}),n}catch{return new Worker("data:text/javascript;charset=utf-8,"+encodeURIComponent(Ti),{name:e?.name})}}var $i=`(function() {
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
`,ya=typeof self<"u"&&self.Blob&&new Blob(["(self.URL || self.webkitURL).revokeObjectURL(self.location.href);",$i],{type:"text/javascript;charset=utf-8"});function jr(e){let t;try{if(t=ya&&(self.URL||self.webkitURL).createObjectURL(ya),!t)throw"";let n=new Worker(t,{name:e?.name});return n.addEventListener("error",()=>{(self.URL||self.webkitURL).revokeObjectURL(t)}),n}catch{return new Worker("data:text/javascript;charset=utf-8,"+encodeURIComponent($i),{name:e?.name})}}var Ps=`:root {
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
}`;_s(Ps);$altcha.algorithms.set("SHA-256",()=>new jr);$altcha.algorithms.set("SHA-384",()=>new jr);$altcha.algorithms.set("SHA-512",()=>new jr);$altcha.algorithms.set("PBKDF2/SHA-256",()=>new zr);$altcha.algorithms.set("PBKDF2/SHA-384",()=>new zr);$altcha.algorithms.set("PBKDF2/SHA-512",()=>new zr);var Ls={ariaLinkLabel:"Altcha (site officiel)",enterCode:"Entrez le code",enterCodeAria:"Entrez le code que vous entendez. Appuyez sur Espace pour \xE9couter l'audio.",error:"\xC9chec de la v\xE9rification. Essayez \xE0 nouveau plus tard.",expired:"La v\xE9rification a expir\xE9. Essayez \xE0 nouveau.",footer:'Prot\xE9g\xE9 par <a href="https://altcha.org/" tabindex="-1" target="_blank" aria-label="Altcha (site officiel)">ALTCHA</a>',getAudioChallenge:"Obtenir un d\xE9fi audio",label:"Je ne suis pas un robot",loading:"Chargement...",reload:"Recharger",verify:"V\xE9rifier",verificationRequired:"V\xE9rification requise !",verified:"V\xE9rifi\xE9",verifying:"V\xE9rification en cours...",waitAlert:"V\xE9rification en cours... veuillez patienter.",cancel:"Annuler",enterCodeFromImage:"Pour continuer, veuillez entrer le code de l'image ci-dessous."};"$altcha"in globalThis&&globalThis.$altcha.i18n.set("fr-fr",Ls);})();
//# sourceMappingURL=altcha.js.map
