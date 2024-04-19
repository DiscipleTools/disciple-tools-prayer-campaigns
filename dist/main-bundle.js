(function(S){typeof define=="function"&&define.amd?define(S):S()})(function(){"use strict";var dt=Object.defineProperty;var lt=(S,z,E)=>z in S?dt(S,z,{enumerable:!0,configurable:!0,writable:!0,value:E}):S[z]=E;var g=(S,z,E)=>(lt(S,typeof z!="symbol"?z+"":z,E),E);/**
 * @license
 * Copyright 2019 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */var He;const S=globalThis,z=S.ShadowRoot&&(S.ShadyCSS===void 0||S.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,E=Symbol(),ye=new WeakMap;let be=class{constructor(e,t,i){if(this._$cssResult$=!0,i!==E)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=t}get styleSheet(){let e=this.o;const t=this.t;if(z&&e===void 0){const i=t!==void 0&&t.length===1;i&&(e=ye.get(t)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),i&&ye.set(t,e))}return e}toString(){return this.cssText}};const Ie=a=>new be(typeof a=="string"?a:a+"",void 0,E),v=(a,...e)=>{const t=a.length===1?a[0]:e.reduce((i,s,n)=>i+(o=>{if(o._$cssResult$===!0)return o.cssText;if(typeof o=="number")return o;throw Error("Value passed to 'css' function must be a 'css' function result: "+o+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(s)+a[n+1],a[0]);return new be(t,a,E)},Ne=(a,e)=>{if(z)a.adoptedStyleSheets=e.map(t=>t instanceof CSSStyleSheet?t:t.styleSheet);else for(const t of e){const i=document.createElement("style"),s=S.litNonce;s!==void 0&&i.setAttribute("nonce",s),i.textContent=t.cssText,a.appendChild(i)}},ve=z?a=>a:a=>a instanceof CSSStyleSheet?(e=>{let t="";for(const i of e.cssRules)t+=i.cssText;return Ie(t)})(a):a;/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const{is:Re,defineProperty:Fe,getOwnPropertyDescriptor:Be,getOwnPropertyNames:We,getOwnPropertySymbols:Ze,getPrototypeOf:Ve}=Object,T=globalThis,$e=T.trustedTypes,Je=$e?$e.emptyScript:"",G=T.reactiveElementPolyfillSupport,I=(a,e)=>a,ee={toAttribute(a,e){switch(e){case Boolean:a=a?Je:null;break;case Object:case Array:a=a==null?a:JSON.stringify(a)}return a},fromAttribute(a,e){let t=a;switch(e){case Boolean:t=a!==null;break;case Number:t=a===null?null:Number(a);break;case Object:case Array:try{t=JSON.parse(a)}catch{t=null}}return t}},xe=(a,e)=>!Re(a,e),ke={attribute:!0,type:String,converter:ee,reflect:!1,hasChanged:xe};Symbol.metadata??(Symbol.metadata=Symbol("metadata")),T.litPropertyMetadata??(T.litPropertyMetadata=new WeakMap);class O extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??(this.l=[])).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,t=ke){if(t.state&&(t.attribute=!1),this._$Ei(),this.elementProperties.set(e,t),!t.noAccessor){const i=Symbol(),s=this.getPropertyDescriptor(e,i,t);s!==void 0&&Fe(this.prototype,e,s)}}static getPropertyDescriptor(e,t,i){const{get:s,set:n}=Be(this.prototype,e)??{get(){return this[t]},set(o){this[t]=o}};return{get(){return s==null?void 0:s.call(this)},set(o){const d=s==null?void 0:s.call(this);n.call(this,o),this.requestUpdate(e,d,i)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??ke}static _$Ei(){if(this.hasOwnProperty(I("elementProperties")))return;const e=Ve(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(I("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(I("properties"))){const t=this.properties,i=[...We(t),...Ze(t)];for(const s of i)this.createProperty(s,t[s])}const e=this[Symbol.metadata];if(e!==null){const t=litPropertyMetadata.get(e);if(t!==void 0)for(const[i,s]of t)this.elementProperties.set(i,s)}this._$Eh=new Map;for(const[t,i]of this.elementProperties){const s=this._$Eu(t,i);s!==void 0&&this._$Eh.set(s,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const t=[];if(Array.isArray(e)){const i=new Set(e.flat(1/0).reverse());for(const s of i)t.unshift(ve(s))}else e!==void 0&&t.push(ve(e));return t}static _$Eu(e,t){const i=t.attribute;return i===!1?void 0:typeof i=="string"?i:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){var e;this._$ES=new Promise(t=>this.enableUpdating=t),this._$AL=new Map,this._$E_(),this.requestUpdate(),(e=this.constructor.l)==null||e.forEach(t=>t(this))}addController(e){var t;(this._$EO??(this._$EO=new Set)).add(e),this.renderRoot!==void 0&&this.isConnected&&((t=e.hostConnected)==null||t.call(e))}removeController(e){var t;(t=this._$EO)==null||t.delete(e)}_$E_(){const e=new Map,t=this.constructor.elementProperties;for(const i of t.keys())this.hasOwnProperty(i)&&(e.set(i,this[i]),delete this[i]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return Ne(e,this.constructor.elementStyles),e}connectedCallback(){var e;this.renderRoot??(this.renderRoot=this.createRenderRoot()),this.enableUpdating(!0),(e=this._$EO)==null||e.forEach(t=>{var i;return(i=t.hostConnected)==null?void 0:i.call(t)})}enableUpdating(e){}disconnectedCallback(){var e;(e=this._$EO)==null||e.forEach(t=>{var i;return(i=t.hostDisconnected)==null?void 0:i.call(t)})}attributeChangedCallback(e,t,i){this._$AK(e,i)}_$EC(e,t){var n;const i=this.constructor.elementProperties.get(e),s=this.constructor._$Eu(e,i);if(s!==void 0&&i.reflect===!0){const o=(((n=i.converter)==null?void 0:n.toAttribute)!==void 0?i.converter:ee).toAttribute(t,i.type);this._$Em=e,o==null?this.removeAttribute(s):this.setAttribute(s,o),this._$Em=null}}_$AK(e,t){var n;const i=this.constructor,s=i._$Eh.get(e);if(s!==void 0&&this._$Em!==s){const o=i.getPropertyOptions(s),d=typeof o.converter=="function"?{fromAttribute:o.converter}:((n=o.converter)==null?void 0:n.fromAttribute)!==void 0?o.converter:ee;this._$Em=s,this[s]=d.fromAttribute(t,o.type),this._$Em=null}}requestUpdate(e,t,i){if(e!==void 0){if(i??(i=this.constructor.getPropertyOptions(e)),!(i.hasChanged??xe)(this[e],t))return;this.P(e,t,i)}this.isUpdatePending===!1&&(this._$ES=this._$ET())}P(e,t,i){this._$AL.has(e)||this._$AL.set(e,t),i.reflect===!0&&this._$Em!==e&&(this._$Ej??(this._$Ej=new Set)).add(e)}async _$ET(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){var i;if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??(this.renderRoot=this.createRenderRoot()),this._$Ep){for(const[n,o]of this._$Ep)this[n]=o;this._$Ep=void 0}const s=this.constructor.elementProperties;if(s.size>0)for(const[n,o]of s)o.wrapped!==!0||this._$AL.has(n)||this[n]===void 0||this.P(n,this[n],o)}let e=!1;const t=this._$AL;try{e=this.shouldUpdate(t),e?(this.willUpdate(t),(i=this._$EO)==null||i.forEach(s=>{var n;return(n=s.hostUpdate)==null?void 0:n.call(s)}),this.update(t)):this._$EU()}catch(s){throw e=!1,this._$EU(),s}e&&this._$AE(t)}willUpdate(e){}_$AE(e){var t;(t=this._$EO)==null||t.forEach(i=>{var s;return(s=i.hostUpdated)==null?void 0:s.call(i)}),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EU(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Ej&&(this._$Ej=this._$Ej.forEach(t=>this._$EC(t,this[t]))),this._$EU()}updated(e){}firstUpdated(e){}}O.elementStyles=[],O.shadowRootOptions={mode:"open"},O[I("elementProperties")]=new Map,O[I("finalized")]=new Map,G==null||G({ReactiveElement:O}),(T.reactiveElementVersions??(T.reactiveElementVersions=[])).push("2.0.4");/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const N=globalThis,J=N.trustedTypes,Se=J?J.createPolicy("lit-html",{createHTML:a=>a}):void 0,ze="$lit$",C=`lit$${Math.random().toFixed(9).slice(2)}$`,Ae="?"+C,Qe=`<${Ae}>`,M=document,R=()=>M.createComment(""),F=a=>a===null||typeof a!="object"&&typeof a!="function",Ee=Array.isArray,Xe=a=>Ee(a)||typeof(a==null?void 0:a[Symbol.iterator])=="function",te=`[ 	
\f\r]`,B=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,Te=/-->/g,Ce=/>/g,j=RegExp(`>|${te}(?:([^\\s"'>=/]+)(${te}*=${te}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),qe=/'/g,Me=/"/g,je=/^(?:script|style|textarea|title)$/i,Ye=a=>(e,...t)=>({_$litType$:a,strings:e,values:t}),l=Ye(1),U=Symbol.for("lit-noChange"),$=Symbol.for("lit-nothing"),De=new WeakMap,D=M.createTreeWalker(M,129);function Oe(a,e){if(!Array.isArray(a)||!a.hasOwnProperty("raw"))throw Error("invalid template strings array");return Se!==void 0?Se.createHTML(e):e}const Ke=(a,e)=>{const t=a.length-1,i=[];let s,n=e===2?"<svg>":"",o=B;for(let d=0;d<t;d++){const r=a[d];let c,_,m=-1,w=0;for(;w<r.length&&(o.lastIndex=w,_=o.exec(r),_!==null);)w=o.lastIndex,o===B?_[1]==="!--"?o=Te:_[1]!==void 0?o=Ce:_[2]!==void 0?(je.test(_[2])&&(s=RegExp("</"+_[2],"g")),o=j):_[3]!==void 0&&(o=j):o===j?_[0]===">"?(o=s??B,m=-1):_[1]===void 0?m=-2:(m=o.lastIndex-_[2].length,c=_[1],o=_[3]===void 0?j:_[3]==='"'?Me:qe):o===Me||o===qe?o=j:o===Te||o===Ce?o=B:(o=j,s=void 0);const p=o===j&&a[d+1].startsWith("/>")?" ":"";n+=o===B?r+Qe:m>=0?(i.push(c),r.slice(0,m)+ze+r.slice(m)+C+p):r+C+(m===-2?d:p)}return[Oe(a,n+(a[t]||"<?>")+(e===2?"</svg>":"")),i]};class W{constructor({strings:e,_$litType$:t},i){let s;this.parts=[];let n=0,o=0;const d=e.length-1,r=this.parts,[c,_]=Ke(e,t);if(this.el=W.createElement(c,i),D.currentNode=this.el.content,t===2){const m=this.el.content.firstChild;m.replaceWith(...m.childNodes)}for(;(s=D.nextNode())!==null&&r.length<d;){if(s.nodeType===1){if(s.hasAttributes())for(const m of s.getAttributeNames())if(m.endsWith(ze)){const w=_[o++],p=s.getAttribute(m).split(C),u=/([.?@])?(.*)/.exec(w);r.push({type:1,index:n,name:u[2],strings:p,ctor:u[1]==="."?et:u[1]==="?"?tt:u[1]==="@"?it:Q}),s.removeAttribute(m)}else m.startsWith(C)&&(r.push({type:6,index:n}),s.removeAttribute(m));if(je.test(s.tagName)){const m=s.textContent.split(C),w=m.length-1;if(w>0){s.textContent=J?J.emptyScript:"";for(let p=0;p<w;p++)s.append(m[p],R()),D.nextNode(),r.push({type:2,index:++n});s.append(m[w],R())}}}else if(s.nodeType===8)if(s.data===Ae)r.push({type:2,index:n});else{let m=-1;for(;(m=s.data.indexOf(C,m+1))!==-1;)r.push({type:7,index:n}),m+=C.length-1}n++}}static createElement(e,t){const i=M.createElement("template");return i.innerHTML=e,i}}function L(a,e,t=a,i){var o,d;if(e===U)return e;let s=i!==void 0?(o=t._$Co)==null?void 0:o[i]:t._$Cl;const n=F(e)?void 0:e._$litDirective$;return(s==null?void 0:s.constructor)!==n&&((d=s==null?void 0:s._$AO)==null||d.call(s,!1),n===void 0?s=void 0:(s=new n(a),s._$AT(a,t,i)),i!==void 0?(t._$Co??(t._$Co=[]))[i]=s:t._$Cl=s),s!==void 0&&(e=L(a,s._$AS(a,e.values),s,i)),e}class Ge{constructor(e,t){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=t}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:t},parts:i}=this._$AD,s=((e==null?void 0:e.creationScope)??M).importNode(t,!0);D.currentNode=s;let n=D.nextNode(),o=0,d=0,r=i[0];for(;r!==void 0;){if(o===r.index){let c;r.type===2?c=new Z(n,n.nextSibling,this,e):r.type===1?c=new r.ctor(n,r.name,r.strings,this,e):r.type===6&&(c=new st(n,this,e)),this._$AV.push(c),r=i[++d]}o!==(r==null?void 0:r.index)&&(n=D.nextNode(),o++)}return D.currentNode=M,s}p(e){let t=0;for(const i of this._$AV)i!==void 0&&(i.strings!==void 0?(i._$AI(e,i,t),t+=i.strings.length-2):i._$AI(e[t])),t++}}class Z{get _$AU(){var e;return((e=this._$AM)==null?void 0:e._$AU)??this._$Cv}constructor(e,t,i,s){this.type=2,this._$AH=$,this._$AN=void 0,this._$AA=e,this._$AB=t,this._$AM=i,this.options=s,this._$Cv=(s==null?void 0:s.isConnected)??!0}get parentNode(){let e=this._$AA.parentNode;const t=this._$AM;return t!==void 0&&(e==null?void 0:e.nodeType)===11&&(e=t.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,t=this){e=L(this,e,t),F(e)?e===$||e==null||e===""?(this._$AH!==$&&this._$AR(),this._$AH=$):e!==this._$AH&&e!==U&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):Xe(e)?this.k(e):this._(e)}S(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.S(e))}_(e){this._$AH!==$&&F(this._$AH)?this._$AA.nextSibling.data=e:this.T(M.createTextNode(e)),this._$AH=e}$(e){var n;const{values:t,_$litType$:i}=e,s=typeof i=="number"?this._$AC(e):(i.el===void 0&&(i.el=W.createElement(Oe(i.h,i.h[0]),this.options)),i);if(((n=this._$AH)==null?void 0:n._$AD)===s)this._$AH.p(t);else{const o=new Ge(s,this),d=o.u(this.options);o.p(t),this.T(d),this._$AH=o}}_$AC(e){let t=De.get(e.strings);return t===void 0&&De.set(e.strings,t=new W(e)),t}k(e){Ee(this._$AH)||(this._$AH=[],this._$AR());const t=this._$AH;let i,s=0;for(const n of e)s===t.length?t.push(i=new Z(this.S(R()),this.S(R()),this,this.options)):i=t[s],i._$AI(n),s++;s<t.length&&(this._$AR(i&&i._$AB.nextSibling,s),t.length=s)}_$AR(e=this._$AA.nextSibling,t){var i;for((i=this._$AP)==null?void 0:i.call(this,!1,!0,t);e&&e!==this._$AB;){const s=e.nextSibling;e.remove(),e=s}}setConnected(e){var t;this._$AM===void 0&&(this._$Cv=e,(t=this._$AP)==null||t.call(this,e))}}class Q{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,t,i,s,n){this.type=1,this._$AH=$,this._$AN=void 0,this.element=e,this.name=t,this._$AM=s,this.options=n,i.length>2||i[0]!==""||i[1]!==""?(this._$AH=Array(i.length-1).fill(new String),this.strings=i):this._$AH=$}_$AI(e,t=this,i,s){const n=this.strings;let o=!1;if(n===void 0)e=L(this,e,t,0),o=!F(e)||e!==this._$AH&&e!==U,o&&(this._$AH=e);else{const d=e;let r,c;for(e=n[0],r=0;r<n.length-1;r++)c=L(this,d[i+r],t,r),c===U&&(c=this._$AH[r]),o||(o=!F(c)||c!==this._$AH[r]),c===$?e=$:e!==$&&(e+=(c??"")+n[r+1]),this._$AH[r]=c}o&&!s&&this.j(e)}j(e){e===$?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}}class et extends Q{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===$?void 0:e}}class tt extends Q{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==$)}}class it extends Q{constructor(e,t,i,s,n){super(e,t,i,s,n),this.type=5}_$AI(e,t=this){if((e=L(this,e,t,0)??$)===U)return;const i=this._$AH,s=e===$&&i!==$||e.capture!==i.capture||e.once!==i.once||e.passive!==i.passive,n=e!==$&&(i===$||s);s&&this.element.removeEventListener(this.name,this,i),n&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){var t;typeof this._$AH=="function"?this._$AH.call(((t=this.options)==null?void 0:t.host)??this.element,e):this._$AH.handleEvent(e)}}class st{constructor(e,t,i){this.element=e,this.type=6,this._$AN=void 0,this._$AM=t,this.options=i}get _$AU(){return this._$AM._$AU}_$AI(e){L(this,e)}}const ie=N.litHtmlPolyfillSupport;ie==null||ie(W,Z),(N.litHtmlVersions??(N.litHtmlVersions=[])).push("3.1.3");const at=(a,e,t)=>{const i=(t==null?void 0:t.renderBefore)??e;let s=i._$litPart$;if(s===void 0){const n=(t==null?void 0:t.renderBefore)??null;i._$litPart$=s=new Z(e.insertBefore(R(),n),n,void 0,t??{})}return s._$AI(a),s};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */class y extends O{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){var t;const e=super.createRenderRoot();return(t=this.renderOptions).renderBefore??(t.renderBefore=e.firstChild),e}update(e){const t=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=at(t,this.renderRoot,this.renderOptions)}connectedCallback(){var e;super.connectedCallback(),(e=this._$Do)==null||e.setConnected(!0)}disconnectedCallback(){var e;super.disconnectedCallback(),(e=this._$Do)==null||e.setConnected(!1)}render(){return U}}y._$litElement$=!0,y.finalized=!0,(He=globalThis.litElementHydrateSupport)==null||He.call(globalThis,{LitElement:y});const se=globalThis.litElementPolyfillSupport;se==null||se({LitElement:y}),(globalThis.litElementVersions??(globalThis.litElementVersions=[])).push("4.0.5");const nt=v`
  .cp-wrapper {
    max-width:1000px;
    margin: 0 auto;
    padding: 2em 1em;
    border-radius: 10px;
    font-size: 1em;
    min-width: 320px;
  }

  .cp-center {
    text-align: center;
  }
  .center-content {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  strong, b {
    font-weight: bold;
  }
  .cp-wrapper {
    font-size: 1rem;
  }
  h3 {
    font-size: 1.5rem;
  }
  h2 {
    font-size: 1.7rem;
  }
  h1, h2, h3 {
    color: black;
  }

  p {
    margin: 10px 0;
  }
  
  p, button, label {
    font-size: 1rem;
  }

  input {
    font-size: 1rem;
    line-height: 1rem;
    color: black;
    border: 1px solid var(--cp-color, dodgerblue);
    border-radius: 5px;
  }
  label {
    display: grid;
    font-weight: bold;
  }
  select, input[type="text"], input[type="email"], input[type="tel"], input[type="password"] {
    min-width: 250px;
    padding: 0 0.5rem;
    min-height: 40px;
    display: block;
  }


  .cp-wrapper.loading-content h2, .cp-wrapper.loading-content p {
    background-color: #ededed;
    border-radius: 100px;
    min-width: 100px;
    min-height: 20px;
    margin-bottom: 5px;
  }

  button {
    color: #fefefe;
    font-size: 1rem;
    border-radius: 5px;
    border: 1px solid transparent;
    font-weight: normal;
    padding: .85rem 1rem;
    cursor:pointer;
    background-color: var( --cp-color, dodgerblue );
    line-height: 1;
  }
  .button-content {
      display: flex;
      align-items: center;
      justify-content: center;
      column-gap: .5em;
  }  
  button:hover {
    background-color: transparent;
    border-color: var( --cp-color, dodgerblue );
    color: var( --cp-color, dodgerblue );
  }
  button[disabled] {
    opacity: .25;
    cursor: not-allowed;
  }
  button.danger {
    background-color: #cc4b37;
  }
  button.danger:hover {
    color: #cc4b37;
    background-color: transparent;
    border-color: #cc4b37;
  }
  button.clear-button {
    color: var( --cp-color, dodgerblue );
    background-color: transparent;
  }
  button.clear-button.danger {
    color: #cc4b37;
    background-color: transparent;
  }
  button.hollow-button {
    background-color: transparent;
    border-color: var( --cp-color, dodgerblue );
    color: var( --cp-color, dodgerblue );
  }
  button.hollow-button.danger {
    border-color: #cc4b37;
    color: #cc4b37;
  }

  a.button {
    text-decoration: none;
    color: #fefefe;
    font-size: 1rem;
    border-radius: 5px;
    border: 1px solid transparent;
    font-weight: normal;
    padding: .85rem 1rem;
    cursor:pointer;
    background-color: var( --cp-color, dodgerblue );
    line-height: 1;
  }
  a.button:hover {
    background-color: transparent;
    border-color: var( --cp-color, dodgerblue );
    color: var( --cp-color, dodgerblue );
  }

  
  .nav-buttons {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 1em;
    column-gap: .5em;
  }
  .button-spinner {
    filter: invert(1);
  }
  button:hover .button-spinner {
    filter: invert(0);    
  }

  label {
    font-size: 1rem;
    margin-bottom: .5rem;
  }
  .form-error {
    color: red;
  }

  .remove-prayer-time-button, .remove-daily-time-button {
    background-color: transparent;
    color: red;
    /* border: black 1px solid; */
    padding: 0 5px;
  }
  .cp-display-selected-times {
    list-style-type: none;
    padding: 0;
  }
  
  

  /**
   * Confirmation section
   */
  .success-confirmation-section {
    display: none;
    margin-top: 20px;
  }

  /**
Loader
 */

  button.loader:not(.disabled):before {
    content: '';
    position: absolute;
    right: 10px;
    top: 50%;
    margin-top: -14px;
    width: 20px;
    height: 20px;
    border: 4px solid;
    border-left-color: transparent;
    border-radius: 50%;
    filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=0);
    opacity: 0;
    -moz-transition-duration: 0.5s;
    -o-transition-duration: 0.5s;
    -webkit-transition-duration: 0.5s;
    transition-duration: 0.5s;
    -moz-transition-property: opacity;
    -o-transition-property: opacity;
    -webkit-transition-property: opacity;
    transition-property: opacity;
    -moz-animation-duration: 1s;
    -webkit-animation-duration: 1s;
    animation-duration: 1s;
    -moz-animation-iteration-count: infinite;
    -webkit-animation-iteration-count: infinite;
    animation-iteration-count: infinite;
    -moz-animation-name: rotate;
    -webkit-animation-name: rotate;
    animation-name: rotate;
    -moz-animation-timing-function: linear;
    -webkit-animation-timing-function: linear;
    animation-timing-function: linear;
    display: none;
  }

  button.loader:not(.disabled):after {
    content: '';
    height: 100%;
    width: 0;
    -moz-transition-delay: 0.5s;
    -o-transition-delay: 0.5s;
    -webkit-transition-delay: 0.5s;
    transition-delay: 0.5s;
    -moz-transition-duration: 0.75s;
    -o-transition-duration: 0.75s;
    -webkit-transition-duration: 0.75s;
    transition-duration: 0.75s;
    -moz-transition-property: width;
    -o-transition-property: width;
    -webkit-transition-property: width;
    transition-property: width;
  }

  button.loader:not(.disabled).loading {
    position: relative;
    pointer-events: none;
    cursor: not-allowed;
    padding-right: 46px;
  }

  button.loader:not(.disabled).loading:not(.expand) {
    text-align: left;
  }

  button.loader:not(.disabled).loading:before {
    -moz-transition-delay: 0.5s;
    -o-transition-delay: 0.5s;
    -webkit-transition-delay: 0.5s;
    transition-delay: 0.5s;
    -moz-transition-duration: 1s;
    -o-transition-duration: 1s;
    -webkit-transition-duration: 1s;
    transition-duration: 1s;
    filter: progid:DXImageTransform.Microsoft.Alpha(enabled=false);
    opacity: 1;
    display: block;
  }

  button.loader:not(.disabled).loading:after {
    -moz-transition-delay: 0s;
    -o-transition-delay: 0s;
    -webkit-transition-delay: 0s;
    transition-delay: 0s;
    width: 20px;
  }

  @keyframes rotate {
    0% {
      -moz-transform: rotate(0deg);
      -ms-transform: rotate(0deg);
      -webkit-transform: rotate(0deg);
      transform: rotate(0deg);
    }
    100% {
      -moz-transform: rotate(360deg);
      -ms-transform: rotate(360deg);
      -webkit-transform: rotate(360deg);
      transform: rotate(360deg);
    }
  }
  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }

  .dt-tag {
    display: inline-block;
    margin: 0 .5rem;
    padding: 0.5em 0.8em;
    font-size: .8rem;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    color: #fff;
    background-color: var(--cp-color, 'dodgerblue');
  }
  .aligned-row {
    display: flex;
    align-items: center;
  }
  
  .disabled {
    opacity: .5;
    cursor: not-allowed;
  }


`;window.campaignStyles=nt;const X=86400;let Y=null,ot=Intl.DateTimeFormat().resolvedOptions().timeZone||"America/Chicago";const A=function(a){return typeof a>"u"?"":typeof a!="string"?a:a.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&apos;")},x=function(a){return Object.fromEntries(Object.entries(a).map(([e,t])=>[e,A(t)]))}(window.campaign_objects.translations);window.campaign_user_data={timezone:ot,recurring_signups:[]},window.set_user_data=function(a,e=!1){let t=!1;if(a.timezone!==window.campaign_user_data.timezone&&(t=!0),window.campaign_user_data={...window.campaign_user_data,...a},t){e||(window.campaign_scripts.days=window.campaign_scripts.calculate_day_times(window.campaign_user_data.timezone,window.campaign_data.start_timestamp,window.campaign_data.end_timestamp,window.campaign_data.current_commitments,window.campaign_data.slot_length));let i=new CustomEvent("campaign_timezone_change",{detail:{timezone:window.campaign_user_data.timezone,days_already_calculated:e}});window.dispatchEvent(i)}},window.campaign_data={campaign_id:null,start_timestamp:0,end_timestamp:null,slot_length:15,duration_options:[{value:5,label:`${x["%s Minutes"].replace("%s",5)}`},{value:10,label:`${x["%s Minutes"].replace("%s",10)}`},{value:15,label:`${x["%s Minutes"].replace("%s",15)}`},{value:30,label:`${x["%s Minutes"].replace("%s",30)}`},{value:60,label:`${x["%s Hours"].replace("%s",1)}`}],coverage:{},enabled_frequencies:[],frequency_options:[{value:"daily",label:x.Daily,days_limit:90,month_limit:3,step:"day"},{value:"weekly",label:x.Weekly,days_limit:180,step:"week",month_limit:6},{value:"monthly",label:x.Monthly,days_limit:365,step:"month",month_limit:12},{value:"pick",label:x["Pick Dates and Times"]}],current_commitments:{},minutes_committed:0,time_committed:""},window.campaign_scripts={time_slot_coverage:{},processing_save:{},days:[],calculate_day_times:function(a=null,e,t,i,s){window.campaign_scripts.processing_save={},window.campaign_scripts.time_slot_coverage={},window.campaign_scripts.time_label_counts={},window.campaign_scripts.missing_slots={};let n=[],o=parseInt(new Date().getTime()/1e3);t||(t=Math.max(o,e)+365*X),a||(a=Intl.DateTimeFormat().resolvedOptions().timeZone||"America/Chicago");let d=window.luxon.DateTime.fromSeconds(e,{zone:a}).startOf("day").toSeconds(),r=window.luxon.DateTime.fromSeconds(e,{zone:a}).startOf("day"),c=parseInt(d),_=window.luxon.DateTime.fromSeconds(c,{zone:a}).toFormat("h:mm a"),m=!1;for(;c<t;){if(!n.length||c>=r.toSeconds()){r=r.plus({days:1});let q=window.luxon.DateTime.fromSeconds(c+X,{zone:a}).toFormat("h:mm a");(m||_!==null&&q!==_)&&(window.campaign_scripts.processing_save={},m=!m),_=window.luxon.DateTime.fromSeconds(c,{zone:a}).toFormat("h:mm a"),d=c;let k=window.luxon.DateTime.fromSeconds(d,{zone:a});n.push({date_time:k,key:d,day_start_zoned:k.startOf("day").toSeconds(),formatted:k.toFormat("MMMM d"),month:k.toFormat("y_MM"),day:k.toFormat("d"),percent:0,slots:[],covered_slots:0,weekday_number:k.toFormat("c")})}let p=c%X,u="";window.campaign_scripts.processing_save[p]?u=window.campaign_scripts.processing_save[p]:(u=window.luxon.DateTime.fromSeconds(c,{zone:a}).toFormat("hh:mm a"),window.campaign_scripts.processing_save[p]=u),c>=e&&c<t&&(n[n.length-1].slots.push({key:c,formatted:u,subscribers:parseInt((i==null?void 0:i[c])||0)}),(c>o||c<t)&&(window.campaign_scripts.time_label_counts[u]||(window.campaign_scripts.time_label_counts[u]=0),window.campaign_scripts.time_label_counts[u]+=1,i[c]?(n[n.length-1].covered_slots+=1,window.campaign_scripts.time_slot_coverage[u]||(window.campaign_scripts.time_slot_coverage[u]=[]),window.campaign_scripts.time_slot_coverage[u].push(i[c])):c>=o&&(window.campaign_scripts.missing_slots[u]||(window.campaign_scripts.missing_slots[u]=[]),window.campaign_scripts.missing_slots[u].push(c)))),c+=s*60}n.forEach(p=>{p.percent=p.covered_slots/p.slots.length*100}),window.campaign_scripts.processing_save={};let w=new CustomEvent("campaign_days_ready",{detail:n});return window.dispatchEvent(w),n},get_campaign_data:function(a){var t;let e=((t=window.subscription_page_data)==null?void 0:t.campaign_id)||window.campaign_objects.magic_link_parts.post_id;if(Y===null){let i=window.campaign_objects.rest_url+window.campaign_objects.magic_link_parts.root+"/v1/"+window.campaign_objects.magic_link_parts.type+"/campaign_info";window.campaign_objects.remote&&(i=window.campaign_objects.rest_url+window.campaign_objects.magic_link_parts.root+"/v1/24hour-router"),Y=jQuery.ajax({type:"GET",data:{action:"get",parts:window.campaign_objects.magic_link_parts,url:"campaign_info",time:new Date().getTime(),campaign_id:e},contentType:"application/json; charset=utf-8",dataType:"json",url:i}),Y.then(s=>{var n;window.campaign_data={...window.campaign_data,...s},window.campaign_data.frequency_options.forEach(o=>{window.campaign_data.enabled_frequencies.includes(o.value)||(o.disabled=!0)}),a=a||((n=s.subscriber_info)==null?void 0:n.timezone)||window.campaign_user_data.timezone,this.days=window.campaign_scripts.calculate_day_times(a,s.start_timestamp,s.end_timestamp,s.current_commitments,s.slot_length),s.subscriber_info&&window.set_user_data(s.subscriber_info,!0)})}return Y},timestamp_to_month_day:function(a,e=null){const t={month:"long",day:"numeric"};return e&&(t.timeZone=e),new Intl.DateTimeFormat("en-US",t).format(a*1e3)},timestamp_to_time:function(a,e=null){const t={hour:"numeric",minute:"numeric"};return e&&(t.timeZone=e),new Intl.DateTimeFormat("en-US",t).format(a*1e3)},timestamp_to_format:(a,e,t=null)=>(t&&(e.timeZone=t),new Intl.DateTimeFormat("en-US",e).format(a*1e3)),ts_to_format:(a,e="y",t)=>{let i={};return t&&(i.zone=t),window.luxon.DateTime.fromSeconds(a,i).toFormat(e)},timestamps_to_summary:function(a,e,t){const i={hour:"numeric",minute:"numeric",timeZone:t};let s="";s=new Intl.DateTimeFormat("en-US",i).format(a*1e3).toString().replace(":00","");let o=new Date(a*1e3),r=(new Date(e*1e3)-o)/6e4;return r<60&&(r=r+" min"),r==60&&(r=r/60+" hr"),r>60&&(r=r/60+" hrs"),s+=" ("+r+")",s},day_start:(a,e)=>{let t=new Date(a*1e3),i=new Date(t.toLocaleString("en-US",{timeZone:e})),s=t.getTime()-i.getTime();return i.setHours(0,0,0,0),(i.getTime()+s)/1e3},get_day_number:(a,e)=>{let t=new Date(a*1e3);return new Date(t.toLocaleString("en-US",{timeZone:e})).getDay()},day_start_timestamp_utc:a=>{let e=new Date(a*1e3);return e.setHours(0,0,0,0),e.getTime()/1e3},start_of_week:(a,e)=>{let t=window.campaign_scripts.get_day_number(a,e),i=new Date((a-t*86400)*1e3);return new Date(i.toLocaleString("en-US",{timeZone:e}))},get_days_of_the_week_initials:(a="en-US",e="long")=>{let t=new Date;const i=864e5,s=new Intl.DateTimeFormat(a,{weekday:e}).format;return[...Array(7).keys()].map(n=>s(new Date().getTime()-(t.getDay()-n)*i))},will_have_daylight_savings(a,e,t){let i=null;for(;e<t;){let s=this.timestamp_to_time(e,a);if(i!==null&&s!==i)return!0;i=s,e+=X}return!1},escapeObject(a){return Object.fromEntries(Object.entries(a).map(([e,t])=>[e,window.campaign_scripts.escapeHTML(t)]))},escapeHTML(a){if(typeof a>"u")return"";if(typeof a!="string")return a;let e=document.createElement("div");return e.textContent=a,e.innerHTML},recurring_time_slot_label(a){let e=window.luxon.DateTime.fromSeconds(a.first,{zone:window.campaign_user_data.timezone}),t=e.toLocaleString({hour:"numeric",minute:"numeric"});const i=window.campaign_data.frequency_options.find(d=>d.value===a.type);let s=i.label;const n=window.campaign_data.duration_options.find(d=>d.value===parseInt(a.duration)).label;return i.value==="weekly"&&(s=x["Every %s"].replace("%s",e.toFormat("cccc"))),x["%1$s at %2$s for %3$s"].replace("%1$s",s).replace("%2$s",t).replace("%3$s",n)},build_calendar_days(a){const e=new Date().getTime()/1e3,t=a.startOf("month").startOf("day");let i=[],s=window.campaign_scripts.days.filter(n=>n.month===a.toFormat("y_MM"));for(let n=0;n<a.daysInMonth;n++){let o=t.plus({days:n}),d=s.find(c=>c.key===o.toSeconds()),r=o.plus({days:1}).toSeconds();d||(d={key:o.toSeconds(),percent:0,day:n+1,formatted:o.toFormat("MMMM d"),slots:[]}),d.disabled=r<e||window.campaign_data.end_timestamp&&o.toSeconds()>window.campaign_data.end_timestamp||r<=window.campaign_data.start_timestamp,i.push(d)}return i},build_selected_times_for_recurring(a,e,t,i=null,s=null){let n=[],o=new Date().getTime()/1e3,d=window.luxon.DateTime.fromSeconds(Math.max(o,window.campaign_scripts.days[0].key),{zone:window.campaign_user_data.timezone}),r=window.campaign_data.frequency_options.find(k=>k.value===e);r.value==="weekly"&&(d=d.set({weekday:parseInt(i)}));let c=d.startOf("day").toSeconds();s&&(c=window.luxon.DateTime.fromSeconds(s,{zone:window.campaign_user_data.timezone}).startOf("day").toSeconds());let _=c+a,m=window.luxon.DateTime.fromSeconds(_,{zone:window.campaign_user_data.timezone}),w=window.luxon.DateTime.fromSeconds(_,{zone:window.campaign_user_data.timezone});if(window.campaign_user_data.recurring_signups.find(k=>k.root===_))return null;let p=window.campaign_data.end_timestamp;p||(p=m.plus({days:r.days_limit}).toSeconds());let u=1;for(;w.toSeconds()<=p;){let k=w.toSeconds(),fe=w.toLocaleString({hour:"2-digit",minute:"2-digit"});!n.find(rt=>rt.time===k)&&k>o&&k>=window.campaign_data.start_timestamp&&n.push({time:k,duration:t,label:fe,day_key:w.startOf("day"),date_time:w}),w=m.plus({[r.step]:u}),u+=1}let q=window.campaign_scripts.recurring_time_slot_label({first:_,type:r.value,duration:t});return{root:_,label:q,type:r.value,first:n[0].date_time,last:n[n.length-1].date_time,time:a,time_label:n[0].label,count:n.length,duration:t,week_day:i,selected_times:n}},submit_prayer_times:function(a,e,t="add"){e.action=t,e.parts=window.campaign_objects.magic_link_parts,e.campaign_id=a,e.timezone=window.campaign_user_data.timezone;let i=window.campaign_objects.rest_url+window.campaign_objects.magic_link_parts.root+"/v1/"+window.campaign_objects.magic_link_parts.type;return window.campaign_objects.remote&&(i=window.campaign_objects.rest_url+window.campaign_objects.magic_link_parts.root+"/v1/24hour-router"),jQuery.ajax({type:"POST",data:JSON.stringify(e),contentType:"application/json; charset=utf-8",dataType:"json",url:i})},get_empty_times(){let a=86400,e=0,t=new Date("2023-01-01");t.setHours(0,0,0,0);let i=t.getTime()/1e3,s=[];for(;e<a;){let n=window.luxon.DateTime.fromSeconds(i+e,{zone:window.campaign_user_data.timezone}),o=n.toFormat("hh:mm a"),d=0,r=n.toFormat(":mm");s.push({key:e,time_formatted:o,minute:r,hour:n.toLocaleString({hour:"2-digit"}),progress:d}),e+=window.campaign_data.slot_length*60}return s}},jQuery(document).ready(function(a){e();function e(){let t=null,i=a(".dt-magic-link-language-selector");a(i).length>0&&(t=a(i).find("option:selected").text().trim());let s=`
        <input id="edit_modal_field_key" type="hidden"/>
        <div id="edit_modal" class="modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${A(x.modals.edit.modal_title)}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table>
                            <tbody>
                                <tr style="background-color: #ffffff;">
                                    <td style="vertical-align: top;  width: 30%;">${A(x.modals.edit.edit_original_string)}</td>
                                    <td id="edit_modal_original_string" style="font-size: 12px; color: #3c3c3c;"></td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="vertical-align: top;  width: 30%;">${A(x.modals.edit.edit_all_languages)}</td>
                                    <td>
                                        <textarea id="edit_modal_all_languages" rows="5" style="min-width: 100%;"></textarea>
                                    </td>
                                </tr>`;t&&(s+=`<tr style="background-color: #ffffff;">
                                    <td
                                      style="vertical-align: top; width: 30%;">${A(x.modals.edit.edit_selected_language)} ${" - ["+A(t)+"]"}</td>
                                    <td>
                                      <textarea id="edit_modal_selected_language" rows="5" style="min-width: 100%;"></textarea>
                                    </td>
                                  </tr>`),s+=`</tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-common edit-close-btn">${A(x.modals.edit.edit_btn_close)}</button>
                        <button class="btn btn-common edit-update-btn">${A(x.modals.edit.edit_btn_update)}</button>
                    </div>
                </div>
            </div>
        </div>`,a("#edit_modal_div").empty().html(s)}a(document).on("click",".edit-btn",function(t){let i=a(t.currentTarget),s=a(i).data("field_key"),n=a(i).data("lang_default"),o=a(i).data("lang_all"),d=a(i).data("lang_selected");a("#edit_modal_field_key").val(s);let r=a("#edit_modal_original_string"),c=a("#edit_modal_all_languages"),_=a("#edit_modal_selected_language");a(r).text(A(n)),a(c).val(A(o)),a(_).val(A(d)),a("#edit_modal").modal("show")}),a(document).on("click",".edit-close-btn",function(t){a("#edit_modal").modal("hide")}),a(document).on("click",".edit-update-btn",function(t){var _;let i=a("#edit_modal_field_key").val(),s=a("#edit_modal_all_languages").val(),n=a("#edit_modal_selected_language").val(),o=a(".dt-magic-link-language-selector").val(),d=((_=window.subscription_page_data)==null?void 0:_.campaign_id)||window.campaign_objects.magic_link_parts.post_id,r=window.campaign_objects.rest_url+window.campaign_objects.magic_link_parts.root+"/v1/"+window.campaign_objects.magic_link_parts.type+"/campaign_edit",c={action:"post",parts:window.campaign_objects.magic_link_parts,url:"campaign_edit",time:new Date().getTime(),campaign_id:d,edit:{field_key:i,lang_all:s}};n!==void 0&&(c.edit.lang_translate=n),o!==void 0&&(c.edit.lang_code=o),jQuery.ajax({type:"POST",data:JSON.stringify(c),contentType:"application/json; charset=utf-8",dataType:"json",url:r,beforeSend:m=>{m.setRequestHeader("X-WP-Nonce",window.campaign_objects.nonce)}}).promise().then(m=>{a("#edit_modal").modal("hide"),m&&m.updated&&location.reload()})})});/**
 * @license
 * Copyright 2021 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function*P(a,e,t=1){const i=e===void 0?0:a;e??(e=a);for(let s=i;t>0?s<e:e<s;s+=t)yield s}/**
 * @license
 * Copyright 2021 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function*H(a,e){if(a!==void 0){let t=0;for(const i of a)yield e(i,t++)}}const b=window.campaign_scripts.escapeObject(window.campaign_objects.translations);function K(a){return b[a]||console.error("'"+a+"' => __( '"+a+"', 'disciple-tools-prayer-campaigns' ),"),b[a]||a}const Ue=86400;class ae extends y{constructor(){super()}render(){return l`
      <button>
        <slot></slot>
      </button>
    `}}g(ae,"styles",[v``]),g(ae,"properties",{prop:{type:String}}),customElements.define("cp-template",ae);class Le extends y{constructor(){super()}render(){return l`
      <button>
        <slot></slot>
      </button>
    `}}g(Le,"styles",[v`
      button {
        color: #fefefe;
        font-size: 1rem;
        border-radius: 5px;
        border: 1px solid transparent;
        font-weight: normal;
        padding: .85rem 1rem;
        cursor:pointer;
        background-color: var( --cp-color, dodgerblue );
      }
      button:hover {
        background-color: transparent;
        border-color: var( --cp-color, dodgerblue );
        color: var( --cp-color, dodgerblue );
      }
      button[disabled] {
        opacity: .25;
        cursor: not-allowed;
      }
      button.clear-button {
        background-color: transparent;
        padding:5px;
      }
    `]),customElements.define("cp-button",Le);class ne extends y{constructor(){super()}timezone_changed(e){let t=e.target.value;Intl.supportedValuesOf("timeZone").includes(t)?this.dispatchEvent(new CustomEvent("change",{detail:e.target.value})):(this.timezone="",this.requestUpdate())}show_picker(){this.timezone="",this.requestUpdate()}render(){return l`
        <p>${b["Detected time zone"]}:
            <button id="change-timezone-button" ?hidden=${!this.timezone} @click=${this.show_picker}>${this.timezone}</button>
            <div ?hidden=${this.timezone.length} >
                <select @change=${this.timezone_changed}>
                    <option value="">${b["Select a timezone"]}</option>
                    ${Intl.supportedValuesOf("timeZone").map(e=>l`<option value="${e}">${e}</option>`)}
                </select>
            </div>
        </p>
    `}}g(ne,"styles",[v`
      button#change-timezone-button {
        background: none!important;
        border: none;
        padding: 0!important;
        color: #069;
        text-decoration: underline;
        cursor: pointer;
      }
    `,window.campaignStyles]),g(ne,"properties",{timezone:{type:String}}),customElements.define("time-zone-picker",ne);class oe extends y{constructor(){super(),this.starting_label=b["Campaign Begins In"],this.ending_label=b["Campaign Ends In"],this.is_finished=b["Campaign Ended"]}connectedCallback(){super.connectedCallback(),this.timezone=this.timezone||Intl.DateTimeFormat().resolvedOptions().timeZone,this.now=new Date().getTime()/1e3,this.end_time&&this.end_time<this.now?this.title=this.is_finished:this.start_time&&this.start_time>this.now?this.title=this.starting_label:this.end_time&&this.end_time>this.now&&(this.title=this.ending_label),!(this.end_time&&this.end_time<this.now||!this.end_time&&this.start_time<this.now)&&(this.interval=window.setInterval(()=>{let e=parseInt(new Date().getTime()/1e3),t=(e<this.start_time?this.start_time:this.end_time)-e,i=Math.floor(t/(60*60*24)),s=Math.floor(t%(60*60*24)/(60*60)),n=Math.floor(t%(60*60)/60),o=Math.floor(t%60);this.label=`${i} ${b.days}, ${s} ${b.hours}, ${n} ${b.minutes}, ${o} ${b.seconds}`,t<0&&(this.title=this.is_finished,window.clearInterval(this.interval))},1e3))}disconnectedCallback(){super.disconnectedCallback(),window.clearInterval(this.interval)}render(){return l`
        <h2>${this.title}</h2>
        <h3>${this.label}</h3>
    `}}g(oe,"styles",[window.campaignStyles,v`
      h2 {
        font-size: 3em;
        text-align: center;
        color: #fff;
        margin-bottom: 0.5rem;
      }
      h3 {
        font-size: 35px;
        color: #fff;
        margin-top: 0;
        margin-bottom: 15px;
      }
      h4 {
        font-size: 20px;
        color: #fff;
      }
    `]),g(oe,"properties",{label:{type:String},title:{type:String},show:{type:Boolean},timezone:{type:String},end_time:{type:Number},start_time:{type:Number},starting_label:{type:String},ending_label:{type:String},is_finished:{type:String}}),customElements.define("counter-row",oe);class re extends y{constructor(){var e;super(),this._form_items={email:"",name:"",receive_pray4movement_news:!0},(e=window.campaign_data.signup_form_fields)==null||e.map(t=>{this._form_items[t.key]=t.default||null}),this.selected_times_count=0,this._loading=!1}_is_email(e){return String(e).match(/^\S+@\S+\.\S+$/)}handleInput(e){let t=e.target.type==="checkbox"?e.target.checked:e.target.value,i=e.target.name;this._form_items[i]=t,this.requestUpdate()}back(){this.dispatchEvent(new CustomEvent("back"))}verify_contact_info(){if(!this._form_items.EMAIL){if(!this._form_items.name||!this._is_email(this._form_items.email)){this.form_error=b["Please enter a valid name or email address"],this.requestUpdate();return}this.dispatchEvent(new CustomEvent("form-items",{detail:this._form_items}))}}render(){var e;return l`
      <div>
          <label for="name">${b.Name}<br>
              <input class="cp-input" type="text" name="name" id="name" placeholder="${b.Name}" required @input=${this.handleInput} />
          </label>
      </div>
      <div>
          <label for="email">${b.Email}<br>
              <input class="cp-input" type="email" name="EMAIL" id="email" placeholder="${b.Email}" @input=${this.handleInput}/>
              <input class="cp-input" type="email" name="email" id="e2" placeholder="${b.Email}" @input=${this.handleInput} />
          </label>
      </div>
      ${window.campaign_objects.dt_campaigns_is_p4m_news_enabled?l`<label for="receive_pray4movement_news" style="font-weight: normal; display: block">
                <input type="checkbox" checked id="receive_pray4movement_news" name="receive_pray4movement_news" @input=${this.handleInput}/>
                ${K("Receive Pray4Movement news and opportunities, and occasional communication from GospelAmbition.org.")}
          </label>`:""}
      
      <!-- Additional Fields -->
      ${(e=window.campaign_data.signup_form_fields)==null?void 0:e.map(t=>{let i=window.campaign_scripts.escapeHTML(t.key),s=window.campaign_scripts.escapeHTML(t.name),n=window.campaign_scripts.escapeHTML(t.description);if(t.type==="text")return l`
            <div>
                <label for="${i}">${s}<br>
                    <input 
                        class="cp-input" 
                        type="text" name="${i}" id="${i}" placeholder="${n}" @input=${this.handleInput}/>
                </label>
            </div>
          `;if(t.type==="boolean")return l`
            <div>
                <label for="${i}" style="font-weight: normal; display: block">
                    <input 
                        type="checkbox"
                        name="${i}" 
                        id="${i}"
                        ?checked=${t.default}
                        @input=${this.handleInput}/>
                    ${n||s}
                </label>
            </div>
          `})}
      
      <div>
          <div id='cp-no-selected-times' style='display: none' class="form-error" >
              ${b["No prayer times selected"]}
          </div>
      </div>

      <div id="cp-form-error" class="form-error" ?hidden=${!this.form_error}>
          ${this.form_error}
      </div>

      <div class="nav-buttons">
          <button 
              class="button-content"
              ?disabled=${!this._form_items.name||!this._is_email(this._form_items.email)||this.selected_times_count===0||this._loading}
              @click=${()=>this.verify_contact_info()}>

              ${b.Next}
              <img ?hidden=${!this._loading} class="button-spinner" src="${window.campaign_objects.plugin_url}spinner.svg" width="22px" alt="spinner"/>
          </button>
      </div>
    `}}g(re,"styles",[v`
      #email {
        display:none;
      }

    `,campaignStyles]),g(re,"properties",{_form_items:{state:!0},_loading:{state:!0},form_error:{state:!0},last_date_label:{state:!0},selected_times_count:{state:!0}}),customElements.define("contact-info",re);class de extends y{constructor(){super(),this.options=[]}handleClick(e){this.value!=e&&(this.value=e,this.dispatchEvent(new CustomEvent("change",{detail:this.value})))}render(){return l`
      ${this.options.filter(e=>!e.disabled).map(e=>{var t;return l`
          <button class="select ${e.value.toString()===((t=this.value)==null?void 0:t.toString())?"selected":""}"
                  ?disabled="${e.disabled}"
                  @click="${()=>this.handleClick(e.value)}"
            value="${e.value}">
                ${e.label}
              <span>${e.desc}</span>
          </button>`})}

    `}}g(de,"styles",[v`
      .select {
        background-color: transparent;
        border: 1px solid var(--cp-color);
        color: var(--cp-color);
        padding: 0.3rem;
        cursor: pointer;
        margin-bottom: 0.3rem;
        font-weight: bold;
        //font-size: 1rem;
        padding-inline-start: 1rem;
        border-radius: 5px;
        width: 100%;
        text-align: start;
        //line-height: ;

      }
      .select.selected {
        border: 1px solid #ccc;
        background-color: var(--cp-color);
        color: #fff;
        opacity: 0.9;
      }
      .select:hover {
        background-color: var(--cp-color);
        color: #fff;
        opacity: 0.5;
      }
      .select:disabled {
        opacity: 0.5;
        cursor: not-allowed;

      }
    `]),g(de,"properties",{value:{type:String},options:{type:Array}}),customElements.define("cp-select",de);class le extends y{constructor(){super(),this.month_to_show=null,this.start_timestamp=window.campaign_data.start_timestamp,this.end_timestamp=window.campaign_data.end_timestamp,this.days=window.campaign_scripts.days,this.selected_times=[]}connectedCallback(){super.connectedCallback(),window.addEventListener("campaign_days_ready",e=>{this.days=e.detail,this.requestUpdate()})}next_view(e){this.month_to_show=e,this.requestUpdate(),this.shadowRoot.querySelectorAll(".selected-time").forEach(t=>t.classList.remove("selected-time"))}day_selected(e,t){this.dispatchEvent(new CustomEvent("day-selected",{detail:t})),this.shadowRoot.querySelectorAll(".selected-time").forEach(i=>i.classList.remove("selected-time")),e.target.classList.add("selected-time")}render(){if(this.days.length===0)return l`<div></div>`;this.end_timestamp||(this.end_timestamp=this.days[this.days.length-1].key);let e=this.selected_times.map(m=>m.day_key),t=window.campaign_scripts.get_days_of_the_week_initials(navigator.language,"narrow"),s=window.luxon.DateTime.now({zone:window.campaign_user_data.timezone}).toSeconds(),n=window.luxon.DateTime.fromSeconds(this.month_to_show||Math.max(this.days[0].key,s,window.campaign_data.start_timestamp),{zone:window.campaign_user_data.timezone}),o=n.startOf("month"),d=window.campaign_scripts.build_calendar_days(n),r=o.weekday,c=n.minus({months:1}).toSeconds(),_=o.plus({months:1}).toSeconds();return l`

      <div class="calendar-wrapper">
        <h3 class="month-title center">
            <button class="month-next" ?disabled="${o.toSeconds()<s}"
                    @click="${m=>this.next_view(c)}">
                <
            </button>
            ${n.toFormat("MMMM y")}
            <button class="month-next" ?disabled="${_>this.end_timestamp}" @click="${m=>this.next_view(_)}">
                >
            </button>
        </h3>
        <div class="calendar">
            ${t.map(m=>l`<div class="day-cell week-day">${m}</div>`)}
            ${H(P(r%7),m=>l`<div class="day-cell disabled-calendar-day"></div>`)}
            ${d.map(m=>l`
                  <div class="day-cell ${m.disabled?"disabled":""} ${e.includes(m.key)?"selected-day":""}"
                       data-day="${window.campaign_scripts.escapeHTML(m.key)}"
                       @click="${w=>!m.disabled&&this.day_selected(w,m.key)}"
                  >
                      ${window.campaign_scripts.escapeHTML(m.day)}
                  </div>`)}
        </div>
      </div>
      `}}g(le,"styles",[v`
      :host {
        display: block;
        container-type: inline-size;
        container-name: calendar;
      }
      .calendar {
        display: grid;
        grid-template-columns: repeat(7, 14cqw);
      }
      .day-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
      }
      .day-cell:hover {
        background-color: #4676fa1a;
        cursor: pointer;
        border-radius: 50%;
      }
      .day-cell.disabled-calendar-day {
        color:lightgrey;
        cursor: not-allowed;
      }
      .week-day {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
        font-weight: bold;
        font-size:clamp(0.75em, 0.65rem + 2cqi, 1em);
      }
      .selected-time {
        color: black;
        border-radius: 50%;
        border: 2px solid;
        background-color: #4676fa1a;
      }
      .selected-day {
        color: white;
        border-radius: 50%;
        border: 2px solid;
        background-color: var(--cp-color);
      }
      .month-title {
        display: flex;
        justify-content: space-between;
        max-width: 280px;
        font-size: 1.2rem;
      }
      .month-next {
        padding: 0.25rem 0.5rem;
      }
    `,window.campaignStyles]),g(le,"properties",{start_timestamp:{type:String},end_timestamp:{type:String},days:{type:Array},selected_times:{type:Array}}),customElements.define("cp-calendar-day-select",le);class ce extends y{constructor(){super(),this.month_to_show=null,this.start_timestamp=window.campaign_data.start_timestamp,this.end_timestamp=window.campaign_data.end_timestamp,this.days=window.campaign_scripts.days,this.selected_times=[]}connectedCallback(){super.connectedCallback(),window.addEventListener("campaign_days_ready",e=>{this.days=e.detail,this.requestUpdate()})}next_view(e){this.month_to_show=e,this.requestUpdate(),this.shadowRoot.querySelectorAll(".selected-time").forEach(t=>t.classList.remove("selected-time"))}day_selected(e,t){this.dispatchEvent(new CustomEvent("day-selected",{detail:t})),this.shadowRoot.querySelectorAll(".selected-time").forEach(i=>i.classList.remove("selected-time")),e.target.classList.add("selected-time")}render(){var w;if(this.days.length===0)return l`<div></div>`;this.end_timestamp||(this.end_timestamp=9999999999);let e=window.campaign_scripts.get_days_of_the_week_initials(navigator.language,"narrow"),i=window.luxon.DateTime.now({zone:window.campaign_user_data.timezone}).toSeconds(),s=window.luxon.DateTime.fromSeconds(this.month_to_show||Math.max(this.days[0].key,i,window.campaign_data.start_timestamp),{zone:window.campaign_user_data.timezone}),n=s.startOf("month"),o=s.endOf("month"),d={};(((w=window.campaign_data.subscriber_info)==null?void 0:w.my_commitments)||[]).filter(p=>p.time_begin>=n.toSeconds()&&p.time_begin<=o.toSeconds()).forEach(p=>{let u=window.luxon.DateTime.fromSeconds(parseInt(p.time_begin),{zone:window.campaign_user_data.timezone}).toFormat("MMMM d");d[u]||(d[u]=0),d[u]++});let r=window.campaign_scripts.build_calendar_days(s),c=n.weekday,_=s.minus({months:1}).toSeconds(),m=s.plus({months:1}).toSeconds();return document.querySelector("#prayer-times").offsetWidth/7,l`

      <div class="calendar-wrapper">
        <h3 class="month-title center">
            <button class="month-next" ?disabled="${n.toSeconds()<i}"
                    @click="${p=>this.next_view(_)}">
                <
            </button>
            ${s.toFormat("MMMM y")}
            <button class="month-next" ?disabled="${m>this.end_timestamp}" @click="${p=>this.next_view(m)}">
                >
            </button>
        </h3>
        <div class="calendar">
            ${e.map(p=>l`<div class="day-cell week-day">${p}</div>`)}
            ${H(P(c%7),p=>l`<div class="day-cell disabled-calendar-day"></div>`)}
            ${r.map(p=>l`
                  <div class="day-cell ${p.disabled?"disabled":""}"
                       data-day="${window.campaign_scripts.escapeHTML(p.key)}"
                       @click="${u=>this.day_selected(u,p.key)}"
                  >
                    <progress-ring class="${p.disabled?"disabled":0}" progress="${window.campaign_scripts.escapeHTML(p.percent)}" text="${window.campaign_scripts.escapeHTML(p.day)}"></progress-ring>
                    <div class="indicator-section">
                      ${H(P(d[p.formatted]||0),u=>l`<span class="prayer-time-indicator"></span>`)}
                    </div>
                  </div>`)}
        </div>
      </div>

      `}}g(ce,"styles",[v`
      :host {
        display: block;
        --size: min(60px, calc((100vw - 2rem) / 7));
      }
      .calendar-wrapper {
        //container-type: inline-size;
        container-name: cp-calendar;
        border-radius: 10px;
        padding: 1em;
        display: block;
      }

      .calendar {
        display: grid;
        grid-template-columns: repeat(7, var(--size));
      }
      .day-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        height: var(--size);
        width: var(--size);
        position: relative;
      }
      .day-cell.enabled-day:hover {
        background-color: #4676fa1a;
        cursor: pointer;
        border-radius: 50%;
      }
      .week-day {
        display: flex;
        align-items: center;
        justify-content: center;
        height: var(--size);
        width: var(--size);
        font-weight: bold;
        font-size:clamp(1em, 2cqw, 0.5em + 1cqi);
      }
      .selected-time {
        //color: black;
        //border-radius: 50%;
        //border: 2px solid;
        //background-color: #4676fa1a;
      }
      .selected-day {
        color: white;
        border-radius: 50%;
        border: 2px solid;
        background-color: var(--cp-color);
      }
      .month-title {
        display: flex;
        justify-content: space-between;
        max-width: calc(var(--size) * 7);
        font-size: 1.2rem;
        align-items: center;
      }
      .month-next {
        //padding: 0.25rem 0.5rem;
      }
      .indicator-section {
        position: absolute;
        bottom: 17%;
        display: flex;
        gap:1px;
      }
      .prayer-time-indicator {
        width: 5px;
        height: 5px;
        background-color: #57d449;
        border-radius: 100px;
      }
    `,window.campaignStyles]),g(ce,"properties",{start_timestamp:{type:String},end_timestamp:{type:String},days:{type:Array},selected_times:{type:Array}}),customElements.define("my-calendar",ce);class me extends y{constructor(){super(),this.days=window.campaign_scripts.days,this.selected_times=[]}connectedCallback(){super.connectedCallback(),setTimeout(()=>{this.shadowRoot.querySelector(".times-container").scrollTop=250}),window.addEventListener("campaign_timezone_change",e=>{this.days=window.campaign_scripts.days,this.requestUpdate()})}render(){this.frequency==="pick"&&this.selected_day&&(this.times=this.get_times()),this.frequency==="daily"&&(this.times=this.get_daily_times()),this.frequency==="weekly"&&this.weekday&&(this.times=this.get_weekly_times()),this.times||(this.times=window.campaign_scripts.get_empty_times());let e=window.luxon.DateTime.now().toSeconds(),t=60/this.slot_length;return l`
        <div class="times-container">
        ${H(P(24),i=>l`
            ${this.times[i*t]?l`
            <div class="prayer-hour prayer-times">
                <div class="hour-cell">
                    ${this.times[i*t].hour}
                </div>
                ${H(P(t),s=>{let n=this.times[i*t+s];return l`
                    <div class="time ${n.progress>=100?"full-progress":""} ${n.selected?"selected-time":""}"
                         @click="${o=>this.time_selected(o,n.key)}"
                         ?disabled="${this.frequency==="pick"&&n.key<e}">
                        <span class="time-label">${n.minute}</span>
                        <span class="control">
                          ${n.progress<100?l`<progress-ring progress="${n.progress}"></progress-ring>`:l`<div class="center-content" style="height:20px;width:20px;">
                                  ${n.coverage_count}
                              </div>`}
                        </span>
                    </div>
                `})}
            </div>`:l``}
        `)}
      </div>
    `}time_selected(e,t){t<parseInt(new Date().getTime()/1e3)&&this.frequency==="pick"||(this.dispatchEvent(new CustomEvent("time-selected",{detail:t})),this.times=window.campaign_scripts.get_empty_times())}get_times(){let e=this.days.find(i=>i.key===this.selected_day),t=[];return e.slots.forEach(i=>{let s=window.luxon.DateTime.fromSeconds(i.key,{zone:window.campaign_user_data.timezone}),n=i.subscribers?100:0;t.push({key:i.key,hour:s.toLocaleString({hour:"2-digit"}),minute:s.toFormat("mm"),progress:n,selected:this.selected_times.find(o=>i.key>=o.time&&i.key<o.time+o.duration*60),coverage_count:i.subscribers})}),t}get_daily_times(){var r,c,_,m,w;let e=window.luxon.DateTime.now({zone:window.campaign_user_data.timezone});e.toSeconds()<window.campaign_data.start_timestamp&&(e=window.luxon.DateTime.fromSeconds(window.campaign_data.start_timestamp,{zone:window.campaign_user_data.timezone}));let t=e.startOf("day").toSeconds(),i=e.plus({months:1}).toSeconds(),s=this.days.filter(p=>p.key>=t&&p.key<=(window.campaign_data.end_timestamp||i)),n={};s.forEach(p=>{p.slots.forEach(u=>{u.key>=e.toSeconds()&&u.subscribers&&(n[u.formatted]||(n[u.formatted]=[]),n[u.formatted].push(u.subscribers))})});let o=[],d=0;for(;d<Ue;){let p=window.luxon.DateTime.fromSeconds(t+d,{zone:window.campaign_user_data.timezone}),u=p.toFormat("hh:mm a"),q=0;window.campaign_data.end_timestamp?q=((c=(r=window.campaign_scripts.time_slot_coverage)==null?void 0:r[u])!=null&&c.length?((m=(_=window.campaign_scripts.time_slot_coverage)==null?void 0:_[u])==null?void 0:m.length)/window.campaign_scripts.time_label_counts[u]*100:0).toFixed(1):q=(n[u]?n[u].length/(s.length-1)*100:0).toFixed(1);let k=p.toFormat(":mm"),fe=(window.campaign_user_data.recurring_signups||[]).find(V=>V.type==="daily"&&d>=V.time&&d<V.time+V.duration*60);o.push({key:d,time_formatted:u,minute:k,hour:p.toLocaleString({hour:"2-digit"}),progress:q,selected:fe,coverage_count:q>=100?Math.min(...((w=window.campaign_scripts.time_slot_coverage)==null?void 0:w[u])||[0]):0}),d+=this.slot_length*60}return o}get_weekly_times(){let e=window.luxon.DateTime.now({zone:window.campaign_user_data.timezone});e.toSeconds()<window.campaign_data.start_timestamp&&(e=window.luxon.DateTime.fromSeconds(window.campaign_data.start_timestamp,{zone:window.campaign_user_data.timezone}));let t=e.startOf("day").toSeconds(),i=this.days.filter(d=>d.key>t&&d.key<=e.plus({months:1}).toSeconds()&&d.weekday_number===this.weekday),s={};i.forEach(d=>{d.slots.forEach(r=>{r.key>=e.toSeconds()&&r.subscribers&&(s[r.formatted]||(s[r.formatted]=[]),s[r.formatted].push(r.subscribers))})});let n=[],o=0;for(;o<Ue;){let d=window.luxon.DateTime.fromSeconds(t+o,{zone:window.campaign_user_data.timezone}),r=d.toFormat("hh:mm a"),c=(s[r]?s[r].length/i.length*100:0).toFixed(1),_=d.toFormat(":mm");n.push({key:o,time_formatted:r,minute:_,hour:d.toLocaleString({hour:"2-digit"}),progress:c,selected:(window.campaign_user_data.recurring_signups||[]).find(m=>m.type==="weekly"&&m.week_day===this.weekday&&o>=m.time&&o<m.time+m.duration*60),coverage_count:c>=100?Math.min(...s[r]||[0]):0}),o+=this.slot_length*60}return n}}g(me,"styles",[v`
      .prayer-hour {
        margin-bottom: 1rem;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
        grid-gap: 1rem 0.3rem;
        max-width: 400px;
      }
      .prayer-hour:hover {
        background-color: #4676fa1a;
      }
      .hour-cell {
        font-size: 0.8rem;
        display: flex;
        align-content: center;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
      }
      .time.full-progress {
          background-color: #00800052;
      }
      .time.selected-time {
        color: white;
        background-color: var(--cp-color);
      }
      progress-ring {
        height: 20px;
        width: 20px;
      }
      .time {
        flex-basis: 20%;
        background-color: #4676fa1a;
        font-size: 0.8rem;
        border-radius: 5px;
        display: flex;
        justify-content: space-between;
        cursor: pointer;
      }
      .time:hover .time-label {
        background-color: var(--cp-color);
        opacity: 0.5;
        color: #fff;
      }
      .time:hover .control{
        background-color: var(--cp-color);
        opacity: 0.8;
        color: #fff;
      }
      .time[disabled] {
        opacity: 0.3;
        cursor: not-allowed;
      }
      .time-label {
        padding: 0.3rem;
        padding-inline-start: 1rem;
        width: 100%;
      }
      .control {
        background-color: #4676fa36;
        display: flex;
        align-items: center;
        padding: 0 0.1rem;
        border-top-right-radius: 5px;
        border-bottom-right-radius: 5px;
      }
      .times-container {
        overflow-y: scroll;
        max-height: 500px;
        padding-inline-end: 10px;
      }
      .center-content {
        display: flex;
        align-items: center;
        justify-content: center;
      }
    `]),g(me,"properties",{slot_length:{type:String},times:{type:Array},selected_day:{type:String},frequency:{type:String},weekday:{type:String},selected_times:{type:Array},recurring_signups:{type:Array}}),customElements.define("cp-times",me);class pe extends y{constructor(){super(),this.email="",this.code=""}handleInput(e){let t=e.target.value;this.code=t,this.dispatchEvent(new CustomEvent("code-changed",{detail:this.code})),this.requestUpdate()}render(){return l`
      <div class="verify-section">
        <p style="text-align: start">
            ${K("Almost there! Finish signing up by activating your account.")}
        </p>
          
        <p style="text-align: start">
            ${K('Click the "Activate Account" button in the email sent to: %s').replace("%s","")}
            <strong>${this.email}</strong>
        </p>
          
        <p style="text-align: start">
            ${K("It will look like this:")}
        </p>
        <p style="margin-top: 1rem; margin-bottom: 1rem; border:1px solid; border-radius: 5px; padding: 4px">
            <img style="width: 100%" src="${window.campaign_objects.plugin_url}assets/activate_account.gif"/>
        </p>
      </div>

    `}}g(pe,"styles",[v`
      .otp-input-wrapper {
        width: 240px;
        text-align: left;
        display: inline-block;
      }
      .otp-input-wrapper input {
        padding: 0;
        width: 264px;
        font-size: 22px;
        font-weight: 600;
        color: #3e3e3e;
        background-color: transparent;
        border: 0;
        margin-left: 2px;
        letter-spacing: 30px;
        font-family: sans-serif !important;
      }
      .otp-input-wrapper input:focus {
        box-shadow: none;
        outline: none;
      }
      .otp-input-wrapper svg {
        position: relative;
        display: block;
        width: 240px;
        height: 2px;
      }
      .verify-section {
        text-align: center;
      }
    `]),g(pe,"properties",{email:{type:String}}),customElements.define("cp-verify",pe);class _e extends y{constructor(){super(),this.font_size=15,this.color="dodgerblue",this.progress=0,this.text=""}render(){return this.progress=parseInt(this.progress).toFixed(),this.color=parseInt(this.progress)>=100?"mediumseagreen":this.color,l`
      <svg>
           <circle
             class="first-circle"
             stroke="${this.color}"
             fill="transparent"
          />
          <circle
             class="second-circle"
             stroke="${this.color}"
             stroke-opacity="0.1"
             fill="transparent"
          />
          <text class="inner-text" x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="1em" dy=".3em">
              ${window.campaign_scripts.escapeHTML(this.text)}
          </text>
      </svg>
      <style>
        :host{
          --progress: ${this.progress};
          --progress2: ${this.progress2};
        }
      </style>
    `}}g(_e,"styles",[v`
    :host {
      display: block;
      --pi: 3.14159265358979;
      --radius: 50cqi;
      --stroke-width: max(3px, 5cqi);
      --normalized-radius: calc(var(--radius) - var(--stroke-width));
      --normalized-radius2: calc(var(--radius) - var(--stroke-width) / 2 + 1);
      --circumference: calc(var(--normalized-radius) * 2 * var(--pi));
      --circumference2: calc(var(--normalized-radius2) * 2 * var(--pi));

      --offset2: calc((var(--progress) / 100 * var(--circumference))*-1);
      --offset: calc( var(--circumference) + var(--offset2));
      --offset3: calc( var(--circumference2) - var(--progress2) / 100 * var(--circumference2));


      height: 95%;
      width: 95%;
      container-type: inline-size;
    }
    .inner-text {
      font-size: clamp(1em, 0.5em + 3cqi, 1.25rem);
    }

    svg {
      width: 100cqi;
      height: 100cqi;
    }

    circle {
      transition: stroke-dashoffset 0.35s;
      transform: rotate(-90deg);
      transform-origin: 50% 50%;
      stroke-width: var(--stroke-width);
      stroke-dasharray: var(--circumference) var(--circumference);
      r: var(--normalized-radius);
      cx: var(--radius);
      cy: var(--radius);
    }

    circle.first-circle {
      stroke-dashoffset: var(--offset);
    }
    circle.second-circle {
      stroke-dashoffset: var(--offset2);
    }
    `]),g(_e,"properties",{text:{type:String},progress:{type:Number},progress2:{type:Number},font_size:{type:Number},color:{type:String}}),customElements.define("progress-ring",_e);class Pe extends y{static get properties(){return{title:{type:String},content:{type:String,state:!0},context:{type:String},isHelp:{type:Boolean},isOpen:{type:Boolean,state:!0},hideHeader:{type:Boolean},hideButton:{type:Boolean},buttonClass:{type:Object},buttonStyle:{type:Object},confirmButtonClass:{type:String}}}constructor(){super(),this.context="default",this.addEventListener("open",e=>this._openModal()),this.addEventListener("close",e=>this._closeModal())}_openModal(){this.isOpen=!0,this.shadowRoot.querySelector("dialog").showModal(),document.querySelector("body").style.overflow="hidden"}_dialogHeader(e){return this.hideHeader?l``:l`
      <header>
            <h1 id="modal-field-title">${this.title}</h1>
            <button @click="${this._cancelModal}" class="toggle">${e}</button>
          </header>
      `}_closeModal(){this.isOpen=!1,this.shadowRoot.querySelector("dialog").close(),document.querySelector("body").style.overflow="initial"}_cancelModal(){this._triggerClose("cancel")}_triggerClose(e){this.dispatchEvent(new CustomEvent("close",{detail:{action:e}}))}_dialogClick(e){if(e.target.tagName!=="DIALOG")return;const t=e.target.getBoundingClientRect();(t.top<=e.clientY&&e.clientY<=t.top+t.height&&t.left<=e.clientX&&e.clientX<=t.left+t.width)===!1&&this._cancelModal()}_dialogKeypress(e){e.key==="Escape"&&this._cancelModal()}firstUpdated(){this.isOpen&&this._openModal()}updated(e){e.has("isOpen")&&this.isOpen&&this._openModal()}_onButtonClick(){this._triggerClose("close")}_onModalConfirm(){this._triggerClose("confirm")}render(){const e=l`
      <svg viewPort="0 0 12 12" version="1.1" width='12' height='12'>
          xmlns="http://www.w3.org/2000/svg">
        <line x1="1" y1="11"
              x2="11" y2="1"
              stroke="currentColor"
              stroke-width="2"/>
        <line x1="1" y1="1"
              x2="11" y2="11"
              stroke="currentColor"
              stroke-width="2"/>
      </svg>
    `;return l`
      <dialog
        id=""
        class="dt-modal"
        @click=${this._dialogClick}
        @keypress=${this._dialogKeypress}
      >
        <form method="dialog" class=${this.hideHeader?"no-header":""}>
          ${this._dialogHeader(e)}
          ${this.content?l`
            <article><p>${this.content}</p></article>
          `:""}
          <article>
            <slot name="content"></slot>
          </article>
          <footer>
            <button
              class="clear-button small"
              data-close=""
              aria-label="Close reveal"
              type="button"
              @click=${this._onButtonClick}
            >
              <slot name="close-button">Close</slot>
            </button>

            <button
              class="button small ${this.confirmButtonClass}"
              data-close=""
              aria-label="Confirm reveal"
              type="button"
              @click=${this._onModalConfirm}
            >
              <slot name="confirm-button">Confirm</slot>
            </button>
          </footer>
        </form>
      </dialog>
    `}}g(Pe,"styles",[window.campaignStyles,v`
      :host {
        display: block;
        font-family: var(--font-family);
      }
      :host:has(dialog[open]) {
        overflow: hidden;
      }

      .dt-modal {
        display: block;
        background: var(--dt-modal-background-color, #fff);
        color: var(--dt-modal-color, #000);
        max-inline-size: min(90vw, 100%);
        max-block-size: min(80vh, 100%);
        max-block-size: min(80dvb, 100%);
        margin: auto;
        height: fit-content;
        padding: var(--dt-modal-padding, 1em);
        position: fixed;
        inset: 0;
        border-radius: 1em;
        border: none;
        box-shadow: var(--shadow-6);
        z-index: 1000;
        transition: opacity 0.1s ease-in-out;
      }

      dialog:not([open]) {
        pointer-events: none;
        opacity: 0;
      }

      dialog::backdrop {
        background: var(--dt-modal-backdrop-color, rgba(0, 0, 0, 0.55));
        animation: var(--dt-modal-animation, fade-in 0.75s);
      }

      @keyframes fade-in {
        from {
          opacity: 0;
        }
        to {
          opacity: 1;
        }
      }

      h1,
      h2,
      h3,
      h4,
      h5,
      h6 {
        line-height: 1.4;
        text-rendering: optimizeLegibility;
        color: inherit;
        font-style: normal;
        font-weight: 300;
        margin: 0;
      }

      form {
        display: grid;
        height: fit-content;
        grid-template-columns: 1fr;
        grid-template-rows: 50px auto 100px;
        grid-template-areas:
          'header'
          'main'
          'footer';
        position: relative;
      }

      form.no-header {
        grid-template-rows: auto auto;
        grid-template-areas:
          'main'
          'footer';
      }

      header {
        grid-area: header;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
      }

      .button.opener {
        color: var(--dt-modal-button-opener-color,var(--dt-modal-button-color, #fff) );
        background: var(--dt-modal-button-opener-background, var(--dt-modal-button-background, #000) );
        border: 0.1em solid var(--dt-modal-button-opener-background, #000);
      }
      button.toggle {
        margin-inline-end: 0;
        margin-inline-start: auto;
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        display: flex;
        align-items: flex-start;
      }

      article {
        grid-area: main;
        overflow: auto;
      }

      footer {
        grid-area: footer;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
      }

  `]),window.customElements.define("dt-modal",Pe);const f=window.campaign_scripts.escapeObject(window.campaign_objects.translations);function h(a){return f[a]||console.error("'"+a+"' => __( '"+a+"', 'disciple-tools-prayer-campaigns' ),"),f[a]||a}class he extends y{constructor(){super(),this._loading=!1,this.campaign_data={start_timestamp:0,end_timestamp:0,slot_length:15,duration_options:{},coverage:{},enabled_frequencies:[]},this.already_signed_up=!1,this._form_items={email:"",name:"",receive_pray4movement_news:!0},this.now=new Date().getTime()/1e3,this.selected_day=null,this.selected_times=[],this.recurring_signups=[],this.show_selected_times=!1,this.timezone=window.campaign_user_data.timezone,this.days=[],this.account_link="",this.get_campaign_data().then(()=>{this.frequency={value:this.campaign_data.enabled_frequencies.length>0?this.campaign_data.enabled_frequencies[0]:""};let e=[];this.campaign_data.slot_length<=5&&e.push({value:5,label:`${f["%s Minutes"].replace("%s",5)}`}),this.campaign_data.slot_length<=10&&e.push({value:10,label:`${f["%s Minutes"].replace("%s",10)}`}),this.campaign_data.slot_length<=15&&e.push({value:15,label:`${f["%s Minutes"].replace("%s",15)}`}),this.campaign_data.slot_length<=30&&e.push({value:30,label:`${f["%s Minutes"].replace("%s",30)}`}),this.campaign_data.slot_length<=60&&e.push({value:60,label:`${f["%s Hour"].replace("%s",1)}`}),this.duration={value:15,options:e},this.week_day={value:"",options:[{value:"1",label:h("Mondays")},{value:"2",label:h("Tuesdays")},{value:"3",label:h("Wednesdays")},{value:"4",label:h("Thursdays")},{value:"5",label:h("Fridays")},{value:"6",label:h("Saturdays")},{value:"7",label:h("Sundays")}]},this.requestUpdate()})}connectedCallback(){super.connectedCallback(),window.addEventListener("campaign_timezone_change",e=>{this.timezone=e.detail.timezone,this.days=window.campaign_scripts.days,this.requestUpdate()})}selected_times_count(){let e=0;return this.recurring_signups.forEach(t=>{e+=t.selected_times.length}),e+=this.selected_times.length,e}get_campaign_data(){return window.campaign_scripts.get_campaign_data().then(e=>(this._view="main",this.campaign_data={...this.campaign_data,...e},this.days=window.campaign_scripts.days,this.requestUpdate(),e))}submit(){this._loading=!0,this.requestUpdate();let e=this.selected_times,t={name:this._form_items.name,email:this._form_items.email,receive_pray4movement_news:this._form_items.receive_pray4movement_news,selected_times:e,recurring_signups:this.recurring_signups};Object.keys(this._form_items).forEach(i=>{t[i]=this._form_items[i]}),window.campaign_scripts.submit_prayer_times(this.campaign_data.campaign_id,t).done(i=>{this.selected_times=[],this._loading=!1,this._view="confirmation",this.requestUpdate()}).fail(i=>{this._loading=!1;let s=l`So sorry. Something went wrong. Please, try again.<br>
          <a href="${window.campaign_scripts.escapeHTML(window.location.href)}">Try Again</a>`;this._form_items.code_error=s,this.requestUpdate()})}handle_contact_info(e){this._form_items=e.detail,this._loading=!0;let t={email:this._form_items.email,parts:window.campaign_objects.magic_link_parts,campaign_id:this.campaign_data.campaign_id,url:"",name:this._form_items.name,receive_pray4movement_news:this._form_items.receive_pray4movement_news,selected_times:this.selected_times,recurring_signups:this.recurring_signups};window.campaign_scripts.submit_prayer_times(this.campaign_data.campaign_id,t).done(i=>{this.selected_times=[],this._loading=!1,this._view="submit",this.requestUpdate();let s=document.querySelector("#features");s||(s=this.shadowRoot.querySelector("#campaign")),s.scrollIntoView({behavior:"smooth",block:"start",inline:"nearest"})}).fail(i=>{var n;console.log(i);let s=l`${h("So sorry. Something went wrong. You can:")} <br>
        <a href="${window.campaign_scripts.escapeHTML(window.location.href)}">${h("Try Again")}</a> <a href="${window.campaign_scripts.escapeHTML(window.location.href)}">${h("Contact Us")}</a>`;((n=i==null?void 0:i.responseJSON)==null?void 0:n.code)==="activate_account"&&(s=h("Please check your email to activate your account before adding more prayer times.")),this._form_items.form_error=s,this._loading=!1,this.requestUpdate()})}show_toast(e="",t="success"){e||(e=f["Prayer Time Selected"]);let i="#4caf50";t==="warn"&&(i="linear-gradient(to right, #f8b500, #f8b500)"),Toastify({text:e,duration:1500,close:!0,gravity:"bottom",position:"center",style:{background:i}}).showToast()}time_selected(e){if(!this.frequency.value)return this.show_toast("Please check step 1","warn");if(this.frequency.value==="weekly"&&!this.week_day.value)return this.show_toast("Please check step 3","warn");if(this.frequency.value==="pick")return this.time_and_day_selected(e);let t=window.campaign_scripts.build_selected_times_for_recurring(e,this.frequency.value,this.duration.value,this.week_day.value);t&&(this.recurring_signups=[...this.recurring_signups,t],window.campaign_user_data.recurring_signups=this.recurring_signups,this.requestUpdate(),this.show_toast())}day_selected(e){this.selected_day=e,setTimeout(()=>{this.requestUpdate()})}time_and_day_selected(e){let t=this.selected_day+e;e>86400&&(t=e);let i=window.luxon.DateTime.fromSeconds(t,{zone:this.timezone}),s=i.toFormat("hh:mm a");if(!this.selected_times.find(o=>o.time===t)&&t>this.now&&t>=this.campaign_data.start_timestamp){const o={time:t,duration:this.duration.value,label:s,day_key:i.startOf("day").toSeconds(),date_time:i};this.selected_times=[...this.selected_times,o]}this.selected_times.sort((o,d)=>o.time-d.time),this.requestUpdate(),this.show_toast()}handle_frequency(e){this.frequency.value=e.detail,this.requestUpdate()}handle_click(e,t){this[e].value=t,this.requestUpdate()}timezone_change(e){this.timezone=e.detail,window.set_user_data({timezone:this.timezone})}remove_recurring_prayer_time(e){this.recurring_signups.splice(e,1),this.requestUpdate()}remove_prayer_time(e){this.selected_times=this.selected_times.filter(t=>t.time!==e),this.requestUpdate()}duration_section(e){var t;return(t=this.duration)!=null&&t.value?l`
      <!--
          Duration
      -->
      <div class="section-div">
          <h2 class="section-title">
              <span class="step-circle">${e}</span>
              <span>${h("I will pray for")}</span></h2>
          <div>
              <cp-select 
                  .value="${this.duration.value}"
                  .options="${this.duration.options}"
                  @change="${i=>this.handle_click("duration",i.detail)}">
              </cp-select>
          </div>
      </div>
    `:l``}frequency_section(e){var t,i;return(t=this.frequency)!=null&&t.value?l`
      <!--
          FREQUENCY
      -->
      <div class="section-div">
          <h2 class="section-title">
              <span class="step-circle">${e}</span>
              <span>${f["How often?"]}</span> <span ?hidden="${(i=this.frequency)==null?void 0:i.value}" class="place-indicator">${f["Start Here"]}</span>
          </h2>
          <cp-select 
              show_desc="${!!this.campaign_data.end_timestamp}"
              .options="${window.campaign_data.frequency_options}"
              .value="${this.frequency.value}"
               @change="${this.handle_frequency}">
          </cp-select>
          <time-zone-picker timezone="${this.timezone}" @change="${this.timezone_change}">
      </div>
    `:l``}week_day_section(e){var t;return(t=this.frequency)!=null&&t.value?l`
      <!--
        Week Day
      -->
      
      <h2 class="section-title">
          <span class="step-circle">${e}</span>
          <span>${f["On which week day?"]}</span>
          <span ?hidden="${this.week_day.value}" class="place-indicator">${f["Continue here"]}</span>
      </h2>
      <div>
          <cp-select 
              .value="${this.week_day.value}"
              .options="${this.week_day.options}"
              @change="${i=>this.handle_click("week_day",i.detail)}">
          </cp-select>
      </div>

    `:l``}calendar_picker_section(e){return l`
    <!--
        Calendar Picker
    -->
    <h2 class="section-title">
        <span class="step-circle">${e}</span>
        <span>${f["Select a Date"]}</span>
        <span ?hidden="${!(this.recurring_signups.length===0&&this.selected_times.length===0)||this.selected_day}" class="place-indicator">${f["Continue here"]}</span>
    </h2>
        <cp-calendar-day-select
            @day-selected="${t=>this.day_selected(t.detail)}"
            start_timestamp="${this.campaign_data.start_timestamp}"
            end_timestamp="${this.campaign_data.end_timestamp}"
            .selected_times="${this.selected_times}"
            .days="${this.days}"
      ></cp-calendar-day-select>
    `}time_picker_section(e){return l`
        <div class="section-div" ?disabled="${!this.frequency.value||this.frequency.value==="weekly"&&!this.week_day.value}">

            <h2 class="section-title">
                <span class="step-circle">${e}</span>
                <span>
                    ${this.frequency.value==="pick"?this.selected_day?l`${h("Select a Time for %s").replace("%s",window.campaign_scripts.ts_to_format(this.selected_day,"DD",this.timezone))}`:l`${h("Select a Day")}`:l`${h("At what time?")}`}
                </span>
                <span
                    ?hidden="${!(this.recurring_signups.length===0&&this.selected_times.length===0)||!(this.frequency.value==="daily"||this.week_day.value||this.selected_day)}"
                    class="place-indicator">${f["Continue here"]}</span>
            </h2>
            <cp-times
                slot_length="${this.campaign_data.slot_length}"
                .frequency="${this.frequency.value}"
                .weekday="${this.week_day.value}"
                .selected_day="${this.selected_day}"
                .selected_times="${this.selected_times}"
                .recurring_signups="${["bob"]}"
                @time-selected="${t=>this.time_selected(t.detail)}">
            </cp-times>
        </div>
    `}contact_info_section(e){return l`
        <div class="section-div" ?hidden="${this.already_signed_up}">
            <h2 class="section-title">
                <span class="step-circle">${e}</span>
                <span>${f["Contact Info"]}</span>
                <span ?hidden="${this.recurring_signups.length===0&&this.selected_times.length===0}" class="place-indicator">${f["Continue here"]}</span>
            </h2>

            <contact-info .selected_times_count="${this.selected_times_count()}"
                          ._loading="${this._loading}"
                          @form-items=${this.handle_contact_info}
                          .form_error=${this._form_items.form_error}
                          @back=${()=>this._view="main"}
            ></contact-info>
        </div>
        <!--
          already signed in
        -->
        <div class="section-div" ?hidden="${!this.already_signed_up}">
            <h2 class="section-title">
                <span class="step-circle">${e}</span>
                <span>${f.Review}</span>
            </h2>

            <div style="text-align: center;margin-top:20px">
                <button ?disabled=${!this.selected_times_count()}
                        @click=${()=>this.submit()}>
                    ${f.Submit}
                    <img ?hidden=${!this._loading} class="button-spinner" src="${window.campaign_objects.plugin_url}spinner.svg" width="22px" alt="spinner"/>
                </button>

            </div>
        </div>
              
        
    `}selected_times_section(){return l`
        <!--
              Mobile Times Floater
          -->
        <div class="mobile selected-times"
             style="padding: 0.5rem; position: fixed; top:60px; right: 0; z-index: 10000;background-color: white; border:1px solid var(--cp-color); ${this.selected_times_count()?"":"display:none"}">
            <div style="text-align: end;display: flex;justify-content: space-between" @click="${e=>{this.show_selected_times=!this.show_selected_times,this.requestUpdate()}}">
                <button ?hidden="${!this.show_selected_times}" class="button" style="padding:0.25rem 0.85rem">
                    ${f.Close}
                </button>
                <span style="display: flex; align-items: center">
                    <img src="${window.campaign_objects.plugin_url}assets/calendar.png" style="width: 2rem;">
                    <span>
                      (${this.selected_times_count()} <span
                        ?hidden="${!this.show_selected_times}">${f["prayer commitments"]}</span>)
                    </span>
                </span>
            </div>
            <div ?hidden="${!this.show_selected_times}" style="margin-top:1rem; max-height:50%; overflow-y: scroll">
                ${this.recurring_signups.map((e,t)=>{let i=this.campaign_data.end_timestamp&&e.last>this.campaign_data.end_timestamp-2592e3;return l`
                        <div class="selected-times selected-time-labels">
                            <div class="selected-time-frequency">
                                <div>${e.label}</div>
                                <div>
                                    <button @click="${s=>this.remove_recurring_prayer_time(t)}"
                                            class="remove-prayer-time-button">
                                        <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                                    </button>
                                </div>
                            </div>
                            <ul>
                                <li>
                                    ${f["Starting on %s"].replace("%s",e.first.toLocaleString({month:"long",day:"numeric"}))}
                                </li>
                                <li>
                                    ${h(i?"Ends on %s":"Renews on %s").replace("%s",e.last.toLocaleString({month:"long",day:"numeric"}))}
                                </li>
                            </ul>
                        </div>
                    `})}
                ${this.selected_times.map((e,t)=>l`
                    <div class="selected-times">
                        <span>${e.date_time.toLocaleString({month:"short",day:"2-digit",hour:"2-digit",minute:"2-digit"})}</span>
                        <button @click="${i=>this.remove_prayer_time(e.time)}" class="remove-prayer-time-button">
                            <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                        </button>
                    </div>
                `)}
            </div>
        </div>

        <!--
            Desktop Selected Times Section
        -->
        <div class="desktop section-div">
            <h2 class="section-title">
                <span class="step-circle">*</span>
                <span>${h("My Prayer Commitments")} (${this.selected_times_count()})</span>
            </h2>
            ${this.recurring_signups.map((e,t)=>{let i=this.campaign_data.end_timestamp&&e.last>this.campaign_data.end_timestamp-2592e3;return l`
                    <div class="selected-times selected-time-labels">
                        <div class="selected-time-frequency">
                            <div>${e.label}</div>
                            <div>
                                <button @click="${s=>this.remove_recurring_prayer_time(t)}"
                                        class="remove-prayer-time-button"><img
                                    src="${window.campaign_objects.plugin_url}assets/delete-red.svg"></button>
                            </div>
                        </div>
                        <ul>
                            <li>
                                ${f["Starting on %s"].replace("%s",e.first.toLocaleString({month:"long",day:"numeric"}))}
                            </li>
                            <li>
                                ${h(i?"Ends on %s":"Renews on %s").replace("%s",e.last.toLocaleString({month:"long",day:"numeric"}))}
                            </li>
                        </ul>
                    </div>
                `})}
            ${this.selected_times.map((e,t)=>l`
                <div class="selected-times">
                          <span class="aligned-row">
                              ${e.date_time.toLocaleString({month:"short",day:"2-digit"})},
                              <span class="dt-tag">${e.date_time.toLocaleString({hour:"2-digit",minute:"2-digit"})}</span>
                              ${h("for %s minutes").replace("%s",e.duration)}
                          </span>
                    <button @click="${i=>this.remove_prayer_time(e.time)}" class="remove-prayer-time-button">
                        <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                    </button>
                </div>
            `)}
        </div>
    `}verify_section(e){var t,i;return l`
        <!--
            Verify
        -->
        <div class="column">
            <div class="section-div">
                <h2 class="section-title" style="display: flex">
                    <span class="step-circle" style="background-color: red"></span>
                    <span style="flex-grow: 1">${h("Pending - Verification Needed")}</span>
                </h2>
                <cp-verify
                    email="${this._form_items.email}"
                    @code-changed=${s=>{this._form_items.code=s.detail,this.requestUpdate()}}
                ></cp-verify>
                <button @click="${()=>this._view="main"}">${h("Back to sign-up")}</button>
                <div class='form-error'
                     ?hidden=${!((t=this._form_items)!=null&&t.code_error)}>
                    ${(i=this._form_items)==null?void 0:i.code_error}
                </div>
            </div>
        </div>
        </div>
    `}confirmation_section(){return l`
        <div class="section-div">
            <h2 class="section-title">
                <span class="step-circle">!</span>
                <span style="flex-grow: 1">${h("Success")}</span>
            </h2>
            <p>
                ${h("Your registration was successful.")}
            </p>
            <p>
                ${h("Check your email for additional details and to manage your account.")}
            </p>
            <div class="nav-buttons">
                <button @click=${()=>window.location.reload()}>${h("Ok")}</button>
                ${window.campaign_objects.remote?"":l`<a class="button" href="${window.campaign_objects.home+"/prayer/list"}">${h("See Prayer Fuel")}`}</a>
            </div>

        </div>
    `}campaign_ended_section(){return l`
        <div class="section-div">
            <h2 class="section-title">
                <span class="step-circle">!</span>
                <span style="flex-grow: 1">${h("Campaign Ended")}</span>
            </h2>
            <br>
            <br>
            <div>
                <a class="button" href="${window.campaign_objects.home+"/prayer/list"}">${h("See Prayer Fuel")}</a>
            </div>
        </div>
    `}render(){var i,s;let e=[{key:"col1",show:this._view==="main",sections:[{key:"duration",show:!0},{key:"frequency",show:!0},{key:"week_day",show:((i=this.frequency)==null?void 0:i.value)==="weekly"},{key:"calendar_picker",show:((s=this.frequency)==null?void 0:s.value)==="pick"}]},{key:"col2",show:this._view==="main",sections:[{key:"time_picker",show:!0}]},{key:"col3",show:this._view==="main",sections:[{key:"contact_info",show:!0},{key:"selected_times",show:!0}]},{key:"verify",show:this._view==="submit",sections:[{key:"verify",show:!0}]},{key:"confirmation",show:this._view==="confirmation",sections:[{key:"confirmation",show:!0}]}];if(this.campaign_data.end_timestamp&&this.campaign_data.end_timestamp<this.now&&(e=[{key:"campaign_ended",show:this.campaign_data.end_timestamp&&this.campaign_data.end_timestamp<this.now,sections:[{key:"campaign_ended",show:!0}]}]),this.days.length===0)return l`<div class="loading"></div>`;if(!this.frequency)return;let t=0;return l`
        <div id="campaign">

            ${e.map(n=>{if(n.show)return l`
                        <div class="column">
                            ${n.sections.map(o=>{if(o.show)return t+=1,this[o.key+"_section"](t)})}
                        </div>
                    `})}
        </div>
      `}}g(he,"styles",[window.campaignStyles,v`
      :host {
        position: relative;
        display: block;
        left: 50%;
        right: 50%;
        width: 100vw;
        margin: 0 -50vw;
        padding: 0 1rem;
        background-color: white;
      }
    `,v`
      .step-circle {
        border-radius: 100px;
        background-color: var(--cp-color);
        color: #fff;
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        margin-right: 0.5rem;
      }
      .section-title {
        font-size: 1.2rem;
      }
      .section-div {
        padding-bottom: 1.5rem;
      }
      label {
        display: block;
      }
      #campaign {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        column-gap: 3rem;
        font-size: 1rem;
        min-height: 500px;
      }

      .selected-times {
        border: 1px solid var(--cp-color);
        border-radius: 5px;
        margin-bottom: 1rem;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
      }
      .selected-time-labels {
        display: flex;
      }
      .selected-time-labels ul{
        margin:0;
      }
      .selected-time-frequency {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      .mobile {
        display:none;
      }
      .desktop {
        display:block;
      }

      .column {
        max-width: 400px;
        flex-basis: 30%;
      }
      @media screen and (max-width: 600px) {
        .time {
          padding-inline-start: 0.3rem;
        }
        #campaign {
          grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        .center-col {
          grid-column: span 1;
        }
        .time-label {
          padding-inline-start: 0.3rem;
        }
        .column {
          width: 100% !important;
          max-width: 100% !important;
          flex-basis: 100%;
        }
        .mobile {
          display:block;
        }
        .desktop {
          display:none;
        }
      }

      .section-div[disabled] {
        opacity: 0.5;
      }
      .place-indicator {
        color: orange;
        font-size: 1rem;
      }
      .remove-prayer-time-button {
        background: none;
        border: none;
        padding: .2rem;
        margin: 0;
        cursor: pointer;
        display: flex;
        justify-content: center;
      }
      .remove-prayer-time-button:hover {
        border: 1px solid red;
      }
      .remove-prayer-time-button img {
        width: 1rem;
      }


    `]),g(he,"properties",{already_signed_up:{type:Boolean},_view:{type:String,state:!0},_loading:{type:Boolean,state:!0}}),customElements.define("campaign-sign-up",he);class ue extends y{constructor(){super(),this.campaign_data={},this.days=[],this.loading=!0}async connectedCallback(){super.connectedCallback(),this.campaign_data=await window.campaign_scripts.get_campaign_data(),this.loading=!1,this.days=window.campaign_scripts.days,this.timezone=window.campaign_user_data.timezone,this.requestUpdate()}render(){let e=new Date().getTime()/1e3,t=window.luxon.DateTime.fromSeconds(Math.max(e,this.campaign_data.start_timestamp));this.campaign_data.end_timestamp&&this.campaign_data.end_timestamp<e&&(this.campaign_data.end_timestamp-this.campaign_data.start_timestamp<86400*60?t=window.luxon.DateTime.fromSeconds(this.campaign_data.start_timestamp):t=window.luxon.DateTime.fromSeconds(this.campaign_data.end_timestamp).minus({month:1}));let i=[];for(let n=0;n<2;n++){let o=t.startOf("month").plus({month:n});if(this.campaign_data.end_timestamp&&o.toSeconds()>this.campaign_data.end_timestamp)continue;let d=window.campaign_scripts.build_calendar_days(t.plus({month:n})),r=0,c=0;d.forEach(_=>{r+=_.covered_slots||0,c+=_.slots.length||0}),i.push({date:t.plus({month:n}),days:d,percentage:((c?r/c:0)*100).toFixed(2),days_covered:(this.campaign_data.slot_length*r/60/24).toFixed(1)})}let s=window.campaign_scripts.get_days_of_the_week_initials(navigator.language,"narrow");return l`
        <div class="calendar-wrapper ${this.loading?"loading":""}">
            ${i.map(n=>l`
                <div class="calendar-month">
                    <h3 class="month-title center">
                        ${n.date.toFormat("MMM y")}
                        <span class="month-percentage">${n.percentage||0}% | ${n.days_covered} ${h("days")}</span>

                    </h3>
                    <div class="calendar">
                        ${s.map(o=>l`<div class="week-day">${o}</div>`)}
                        ${H(P(n.date.startOf("month").weekday%7),o=>l`<div class="day-cell disabled-calendar-day"></div>`)}
                        ${n.days.map(o=>l`
                                <div class="day-cell
                                     ${o.disabled?"disabled-calendar-day":"day-in-select-calendar"}"
                                data-day="${window.campaign_scripts.escapeHTML(o.key)}"
                                >
                                ${o.disabled&&(o.key<window.campaign_data.start_timestamp||o.key>window.campaign_data.end_timestamp)?window.campaign_scripts.escapeHTML(o.day):l`
                                    <progress-ring class="progress-ring" progress="${window.campaign_scripts.escapeHTML(o.percent)}" text="${window.campaign_scripts.escapeHTML(o.day)}"></progress-ring>
                                `}
                                </div>`)}
                    </div>
                </div>
            </div>
        `)}
    `}}g(ue,"styles",[v`
    :host {
    }
      .calendar-wrapper {
        container-type: inline-size;
        container-name: cp-calendar;
        background-color: #f8f9fad1;
        border-radius: 10px;
        padding: 1em;
        display: block;
      }
      .calendar-month {
        display: block;
        vertical-align: top;
      }
      .month-title {
        display: flex;
        justify-content: space-between;
        max-width: 280px;
        color: var(--cp-color);
        margin:0;
      }
      .month-title .month-percentage {
        color: black; font-size:1.2rem;
      }
      .calendar {
        display: grid;
        grid-template-columns: repeat(7, 12.5cqw);
        gap: 0.3rem;
        margin-bottom: 1rem;
        justify-items: center;
      }
      .day-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 14cqw;
        width: 14cqw;
        font-size: 15px;
      }

      @container cp-calendar (min-width: 250px) {
        .day-cell {
          height: 15cqw;
          width: 15cqw;
        }
        .week-day {
          height: 15cqw;
          width: 15cqw;
        }
      }
      .week-day {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 14cqw;
        width: 14cqw;
        color:black;
        font-size: clamp(1em, 2cqw, 0.5em + 1cqi);
        font-weight:550;
      }

      @container cp-calendar (min-width: 350px) {
        .week-day {
          height: 7.5cqw;
          width: 15cqw;
        }
      }

      .loading {
        min-height: 600px;
      }
      .disabled-calendar-day {
        color: #c4c4c4;
      }
    `]),g(ue,"properties",{prop:{type:String}}),customElements.define("cp-calendar",ue);class ge extends y{constructor(){super()}async connectedCallback(){super.connectedCallback(),this.campaign_data=await window.campaign_scripts.get_campaign_data(),this.loading=!1,this.days=window.campaign_scripts.days,this.timezone=window.campaign_user_data.timezone,this.requestUpdate()}render(){return this.campaign_data?l`
    <div class="cp-progress-wrapper cp-wrapper">
        <div id="main-progress" class="cp-center" style="display: flex;justify-content: center">
            <progress-ring 
               style="max-width: 150px"
               progress="${this.campaign_data.coverage_percent||0}"
               progress2="0"
               text="${this.campaign_data.coverage_percent||0}%"
               text2="">
            </progress-ring>
        </div>
        <div style="color: rgba(0,0,0,0.57); text-align: center">${f["Percentage covered in prayer"]}</div>
        <div style="color: rgba(0,0,0,0.57); text-align: center" id="cp-time-committed-display">${f["%s committed"].replace("%s",this.campaign_data.time_committed)}</div>
    </div>
    `:l`<div class="loading"></div>`}}g(ge,"styles",[v`

    `]),g(ge,"properties",{prop:{type:String}}),customElements.define("cp-percentage",ge);class we extends y{constructor(){super(),this.selected_recurring_signup_to_delete=null,this._selected_time_to_delete=null,this._delete_modal_open=!1,this._extend_modal_open=!1,this._renew_modal_open=!1,this._change_times_modal_open=!1,this._extend_modal_message="Def",this.change_time_details=null}async connectedCallback(){super.connectedCallback(),this.campaign_data=await window.campaign_scripts.get_campaign_data(),this.timezone=window.campaign_user_data.timezone,this.requestUpdate(),window.addEventListener("campaign_timezone_change",e=>{this.timezone=e.detail.timezone,this.days=window.campaign_scripts.days,this.requestUpdate()})}render(){if(!window.campaign_data.subscriber_info)return;let e=new Date().getTime()/1e3;return this.selected_times=window.campaign_data.subscriber_info.my_commitments,this.my_recurring=window.campaign_data.subscriber_info.my_recurring,this.recurring_signups=window.campaign_data.subscriber_info.my_recurring_signups,this.recurring_signups.sort((t,i)=>i.last-t.last),l`
        <!--delete modal-->
        <dt-modal
            .isOpen="${this._delete_modal_open}"
            title="${h("Delete Prayer Times")}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${t=>this.delete_times_modal_closed(t)}"
        >
        <p slot="content">${h("Really delete these prayer times?")}</p>
        </dt-modal>
        <dt-modal
            .isOpen="${this._delete_time_modal_open}"
            title="${h("Delete Prayer Time")}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${t=>this.delete_time_modal_closed(t)}"
        >
        <p slot="content">${h("Really delete this prayer time?")}</p>
        </dt-modal>

        <!--extend modal-->
        <dt-modal
            .isOpen="${this._extend_modal_open}"
            .content="${this._extend_modal_message}"
            title="${h("Extend Prayer Times")}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${t=>this.extend_times_modal_closed(t)}" >
        </dt-modal>
        <!--extend modal-->
        <dt-modal
            .isOpen="${this._renew_modal_open}"
            .content="${this._renew_modal_message}"
            title="${h("Renew Prayer Times")}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${t=>this.extend_times_modal_closed(t,!0)}" >
        </dt-modal>

        <!--change times modal-->
        <dt-modal
            .isOpen="${this._change_times_modal_open}"
            title="${h("Change Prayer Time")}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${t=>this.change_times_modal_closed(t)}" >
            <p slot="content">${this.change_time_details?l`
                ${h("Your current prayer time is %s").replace("%s",window.luxon.DateTime.fromSeconds(this.change_time_details.first,{zone:this.timezone}).toLocaleString({hour:"2-digit",minute:"2-digit"}))}
                <br>
                <br>
                <strong>${h("Select a new time:")}</strong>
                ${this.build_select_for_day_times()}
            `:""}</p>
        </dt-modal>

        ${(this.recurring_signups||[]).map((t,i)=>{let s=this.campaign_data.end_timestamp&&t.last>this.campaign_data.end_timestamp-2592e3,n=86400,o=!s&&t.last<e+n*60&&t.last>e-n*14,d=!s&&t.last<e-n*14;const r=window.campaign_data.subscriber_info.my_commitments.filter(c=>t.report_id==c.recurring_id);return l`
            <div class="selected-times">
                <div class="selected-time-content">
                  <div class="title-row">
                      <h3>${window.luxon.DateTime.fromSeconds(t.first,{zone:this.timezone}).toFormat("DD")} - ${window.luxon.DateTime.fromSeconds(t.last,{zone:this.timezone}).toFormat("DD")}</h3>
                      <button ?hidden="${!o}" class="clear-button" @click="${()=>this.open_extend_times_modal(t.report_id)}">${h("extend")}</button>
                      <button ?hidden="${!d}" class="clear-button" @click="${()=>this.open_extend_times_modal(t.report_id,!0)}">${h("renew")}</button>
                  </div>
                  <div>
                      <strong>${window.campaign_scripts.recurring_time_slot_label(t)}</strong>
                      <button @click="${c=>this.open_change_time_modal(c,t.report_id)}"
                          class="clear-button">${h("change time")}</button>
                  </div>
                  <div class="selected-time-actions">
                      <button class="clear-button" @click="${c=>{t.display_times=!t.display_times,this.requestUpdate()}}">
                          ${h("See prayer times")} (${r.length})
                      </button>
                      <button class="clear-button danger loader" @click="${c=>this.open_delete_times_modal(c,t.report_id)}">
                          ${h("Remove all")}
                      </button>
                  </div>
                </div>
                <div style="margin-top:20px" ?hidden="${!t.display_times}">
                    ${r.map(c=>l`
                        <div class="remove-row">
                            <span>${window.luxon.DateTime.fromSeconds(parseInt(c.time_begin),{zone:this.timezone}).toLocaleString({month:"short",day:"2-digit",hour:"2-digit",minute:"2-digit"})}</span>
                            <button ?disabled="${c.time_begin<e}" @click="${_=>this.open_delete_time_modal(_,c.report_id)}"
                                    class="remove-prayer-times-button clear-button">
                                <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                            </button>
                        </div>
                    `)}
                </div>

            </div>
        `})}
        ${window.campaign_data.subscriber_info.my_commitments.filter(t=>t.type==="selected_time").map((t,i)=>{const s=window.luxon.DateTime.fromSeconds(t.time_begin,{zone:this.timezone});return l`
            <div class="selected-times">
                <div class="selected-time-content">
                  <div style="display: flex; justify-content: space-between">
                    <div class="aligned-row">
                      <h3>${s.toFormat("DD")}</h3>
                      <span class="dt-tag">${s.toLocaleString({hour:"numeric",minute:"numeric",hour12:!0})}</span>
                      ${h("for %s minutes").replace("%s",(t.time_end-t.time_begin)/60)}
                    </div>
                    <button ?disabled="${t.time_begin<e}" class="clear-button danger loader remove-prayer-times-button" @click="${n=>this.open_delete_time_modal(n,t.report_id)}">
                        <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                    </button>
                  </div>
                </div>
            </div>
        `})}

    `}build_select_for_day_times(){let e=window.campaign_scripts.get_empty_times();return l`<select @change="${t=>this.change_time_details.new_time=t.target.value}">
        <option value="">${h("Select a time")}</option>
        ${e.map(t=>l`<option value="${t.key}">${t.time_formatted}</option>`)}
    </select>
    `}delete_recurring_time(){let e={action:"delete_recurring_signup",report_id:this.selected_recurring_signup_to_delete,parts:window.subscription_page_data.parts};jQuery.ajax({type:"POST",data:JSON.stringify(e),contentType:"application/json; charset=utf-8",dataType:"json",url:window.subscription_page_data.root+window.subscription_page_data.parts.root+"/v1/"+window.subscription_page_data.parts.type,beforeSend:function(t){t.setRequestHeader("X-WP-Nonce",window.subscription_page_data.nonce)}}).then(t=>{window.location.reload()})}delete_time(){let e=this._selected_time_to_delete,t={action:"delete",report_id:this._selected_time_to_delete,parts:window.subscription_page_data.parts};jQuery.ajax({type:"POST",data:JSON.stringify(t),contentType:"application/json; charset=utf-8",dataType:"json",url:window.subscription_page_data.root+window.subscription_page_data.parts.root+"/v1/"+window.subscription_page_data.parts.type,beforeSend:function(i){i.setRequestHeader("X-WP-Nonce",window.subscription_page_data.nonce)}}).then(i=>{window.campaign_data.subscriber_info.my_commitments=window.campaign_data.subscriber_info.my_commitments.filter(s=>s.report_id!==e),this._selected_time_to_delete=null,this._delete_time_modal_open=!1,this.requestUpdate()})}open_delete_times_modal(e,t){this.recurring_signups.find(s=>s.report_id===t)&&(this.selected_recurring_signup_to_delete=t,this._delete_modal_open=!0)}open_delete_time_modal(e,t){this.selected_times.find(s=>s.report_id===t)&&(this._selected_time_to_delete=t,this._delete_time_modal_open=!0)}delete_times_modal_closed(e){var t;this._delete_modal_open=!1,((t=e.detail)==null?void 0:t.action)==="confirm"&&this.delete_recurring_time()}delete_time_modal_closed(e){var t;((t=e.detail)==null?void 0:t.action)==="confirm"&&this.delete_time(),this._selected_time_to_delete=null,this._delete_time_modal_open=!1}open_extend_times_modal(e,t){const i=this.recurring_signups.find(n=>n.report_id===e);if(!i)return;this.selected_recurring_signup_to_extend=e;let s=window.campaign_data.frequency_options.find(n=>n.value===i.type);t?(this._renew_modal_message="Renew for %s months?".replace("%s",s.month_limit),this._renew_modal_open=!0):(this._extend_modal_message="Extend for %s months?".replace("%s",s.month_limit),this._extend_modal_open=!0)}extend_times_modal_closed(e,t=!1){var i;if(this._extend_modal_open=!1,((i=e.detail)==null?void 0:i.action)==="confirm"){let s=this.recurring_signups.find(d=>d.report_id===this.selected_recurring_signup_to_extend);if(!s)return;(!s.time||s.time==0)&&(s.time=s.first%(86400*24));let n=window.campaign_scripts.build_selected_times_for_recurring(s.time,s.type,s.duration,s.week_day||null,t?null:s.last);n.report_id=s.report_id;let o=window.campaign_data.subscriber_info.my_commitments.filter(d=>s.report_id===d.recurring_id).map(d=>parseInt(d.time_begin));if(n.selected_times=n.selected_times.filter(d=>!o.includes(d.time)),t){let d={recurring_signups:[n]};window.campaign_scripts.submit_prayer_times(s.campaign_id,d,"add").then(r=>{window.location.reload()})}else window.campaign_scripts.submit_prayer_times(s.campaign_id,n,"update_recurring_signup").then(d=>{window.location.reload()})}}open_change_time_modal(e,t){const i=this.recurring_signups.find(s=>s.report_id===t);i&&(this.change_time_details=i,this._change_times_modal_open=!0)}change_times_modal_closed(e){var t,i;if(((t=e.detail)==null?void 0:t.action)==="confirm"&&((i=this.change_time_details)!=null&&i.time)){let s={report_id:this.change_time_details.report_id,offset:this.change_time_details.new_time-this.change_time_details.time,time:this.change_time_details.new_time};window.campaign_scripts.submit_prayer_times(this.change_time_details.campaign_id,s,"change_times").then(n=>{window.location.reload()})}this.change_time_details=null,this._change_times_modal_open=!1}}g(we,"styles",[window.campaignStyles,v`
      .remove-prayer-times-button {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
      }
      .remove-prayer-times-button:hover {
        border: 1px solid red;
      }
      .remove-prayer-times-button img {
        width: 1rem;

      }
      .selected-times {
        //background-color: rgba(70, 118, 250, 0.1);
        border-radius: 5px;
        border: 1px solid var(--cp-color);
        margin-bottom: 2rem;
        padding: 1rem;
        justify-content: space-between;
        box-shadow: 10px 10px 5px var(--cp-color-light);
      }
      .selected-time-content {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap:5px;
      }
      .selected-time-actions {
        display: flex;
        justify-content: space-between;
      }
      .selected-time-content h3 {
        margin: 0;
        font-size: 1.2rem;
      }
      .selected-time-content .title-row {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
      }
      .selected-time-content .title-row .dt-tag{

        margin-inline-start: 10px;
      }
      button.hollow-button {
        padding: 0.5rem;
      }
      .remove-row {
        display: flex;
        align-items: center;
      }
    `]),g(we,"properties",{prop:{type:String},_delete_modal_open:{type:Boolean,state:!0},_delete_time_modal_open:{type:Boolean,state:!0},_extend_modal_open:{type:Boolean,state:!0},_extend_modal_message:{type:String,state:!0},_renew_modal_open:{type:Boolean,state:!0},_renew_modal_message:{type:String,state:!0},_change_times_modal_open:{type:String,state:!0}}),customElements.define("campaign-subscriptions",we)});
