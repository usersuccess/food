(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-product-search-search"],{"26f3b":function(t,e,a){var n=a("24fb");e=n(!1),e.push([t.i,".search-wrap .index-search-box .search-box[data-v-2c80d3a6]{padding:0 %?20?%;height:%?64?%;line-height:%?64?%;background:#f7f7f7;border-radius:%?50?%;overflow:hidden;font-size:%?28?%;color:#999;box-sizing:border-box}.search-wrap .index-search-box uni-button[data-v-2c80d3a6]{height:%?78?%;line-height:%?78?%;border:solid %?1?% #ccc;color:#333;background:#fff}.before-search[data-v-2c80d3a6]{display:flex;justify-content:flex-start;align-items:center;flex-flow:wrap}.before-search .item[data-v-2c80d3a6]{padding:%?16?%;margin-right:%?16?%;margin-bottom:%?16?%;border-radius:%?8?%;color:#686868;background:#f0f2f5;font-size:%?24?%}",""]),t.exports=e},"48d0":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n={data:function(){return{form:{},arr:[]}},mounted:function(){this.getData()},methods:{getData:function(){var t=this;uni.getStorage({key:"search_list",success:function(e){null!=e&&null!=e.data&&(t.arr=e.data)}})},search:function(t){var e=null;if(null!=t)e=t;else{e=this.form.keyWord;var a=this.arr;if("undefined"==typeof e||null==e||""==e)return uni.showToast({title:"请输入搜索的关键字",icon:"none",duration:2e3}),!1;a.push(e),uni.setStorage({key:"search_list",data:a,success:function(){console.log("success")}})}var n=0,r="all";uni.navigateTo({url:"/pages/product/list/list?search="+e+"&category_id="+n+"&sortType="+r})},clearStorage:function(){var t=this;uni.removeStorage({key:"search_list",success:function(e){t.arr=[]}})}}};e.default=n},"5d14":function(t,e,a){"use strict";a.r(e);var n=a("d3f6"),r=a("9d85");for(var i in r)"default"!==i&&function(t){a.d(e,t,(function(){return r[t]}))}(i);a("8070");var s,o=a("f0c5"),c=Object(o["a"])(r["default"],n["b"],n["c"],!1,null,"2c80d3a6",null,!1,n["a"],s);e["default"]=c.exports},8070:function(t,e,a){"use strict";var n=a("d809"),r=a.n(n);r.a},"9d85":function(t,e,a){"use strict";a.r(e);var n=a("48d0"),r=a.n(n);for(var i in n)"default"!==i&&function(t){a.d(e,t,(function(){return n[t]}))}(i);e["default"]=r.a},d3f6:function(t,e,a){"use strict";var n;a.d(e,"b",(function(){return r})),a.d(e,"c",(function(){return i})),a.d(e,"a",(function(){return n}));var r=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",{staticClass:"search-wrap"},[a("v-uni-view",{staticClass:"index-search-box d-b-c",attrs:{id:"searchBox"}},[a("v-uni-view",{staticClass:"index-search t-c flex-1"},[a("span",{staticClass:"icon iconfont icon-sousuo"}),a("v-uni-input",{staticClass:"flex-1 ml10 f30 gray3",attrs:{type:"text",value:"","placeholder-class":"f24 gray6",placeholder:"输入你要的商品","confirm-type":"search"},on:{confirm:function(e){arguments[0]=e=t.$handleEvent(e),t.search()}},model:{value:t.form.keyWord,callback:function(e){t.$set(t.form,"keyWord",e)},expression:"form.keyWord"}})],1)],1),a("v-uni-view",{staticClass:"p-0-20 bg-white"},[a("v-uni-view",{staticClass:"group-hd"},[a("v-uni-view",{staticClass:"left"},[a("v-uni-text",{staticClass:"min-name"},[t._v("最近搜索")])],1),a("v-uni-view",{staticClass:"right"},[a("span",{staticClass:"icon iconfont icon-lajitong",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clearStorage.apply(void 0,arguments)}}})])],1),a("v-uni-view",{staticClass:"before-search"},t._l(t.arr,(function(e,n){return a("v-uni-text",{key:n,staticClass:"item",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.search(t.arr[n])}}},[t._v(t._s(t.arr[n]))])})),1)],1)],1)},i=[]},d809:function(t,e,a){var n=a("26f3b");"string"===typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var r=a("4f06").default;r("875765b6",n,!0,{sourceMap:!1,shadowMode:!1})}}]);