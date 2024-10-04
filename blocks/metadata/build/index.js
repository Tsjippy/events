(()=>{"use strict";var e,t={825:()=>{const e=window.wp.blocks,t=window.wp.i18n,r=window.wp.element,a=window.wp.data,n=window.wp.coreData,s=window.wp.components,l=window.wp.blockEditor,i=window.ReactJSXRuntime,o=JSON.parse('{"UU":"sim/eventmeta"}');(0,e.registerBlockType)(o.UU,{icon:"calendar",edit:({setAttributes:e,attributes:o})=>{const d=(0,l.useBlockProps)(),c=(0,a.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[p,u]=(0,n.useEntityProp)("postType",c,"meta"),[x,h]=(0,r.useState)("");(0,r.useEffect)((()=>{if(""!=p.eventdetails&&null!=p.eventdetails){let e=JSON.parse(p.eventdetails);null==e.repeat&&(e.repeat={interval:1}),null==e.starttime&&(e.starttime="00:00"),h(e),e.organizer&&g(e.organizer),e.location&&g(e.location)}}),[p]);const m=(e,t,r="")=>{let a={...x};if("startdate"==e)a.startdate=t.split("T")[0],(null==a.enddate||a.enddate<a.startdate)&&(a.enddate=t.split("T")[0]);else if("enddate"==e)a.enddate=t.split("T")[0];else if("starttime"==e){let e=t.split("T")[1];e=e.split(":"),a.starttime=e[0]+":"+e[1]}else if("endtime"==e){let e=t.split("T")[1];e=e.split(":"),a.endtime=e[0]+":"+e[1]}else if(e.startsWith("repeat")){null==a.repeat&&(a.repeat={});let n=e.split("-")[1];if("excludedates"==n||"includedates"==n){null==a.repeat[n]&&(a.repeat[n]=[]);let e=t.split("T")[0],r="added";a.repeat[n].includes(e)?(a.repeat[n]=a.repeat[n].filter((t=>t!=e)),r="removed"):a.repeat[n].push(e),wp.data.dispatch("core/notices").createNotice("success",`Succesfully ${r} the date`,{type:"snackbar",isDismissible:!0})}else"weeks"==n||"months"==n?(null==a.repeat[n]&&(a.repeat[n]=[]),a.repeat[n].includes(r)?a.repeat[n]=a.repeat[n].filter((e=>e!=r)):a.repeat[n].push(r)):a.repeat[n]=t}else a[e]=t;"organizer_id"==e?(j(r),a.organizer=r):"location_id"==e&&(g(r),a.location=r),a=JSON.stringify(a),u({...p,eventdetails:a})},[_,j]=(0,r.useState)(x.organizer),[v,g]=(0,r.useState)(x.location),{users:f,userResolved:y}=(0,a.useSelect)((e=>{if(!_)return{users:[],userResolved:!0};const t={search:_,per_page:100,context:"view"};return{users:e(n.store).getUsers(t),userResolved:e(n.store).hasFinishedResolution("getUsers",[t])}}),[_]),{locations:b,locationResolved:R}=(0,a.useSelect)((e=>{if(!v)return{locations:[],locationResolved:!0};const t=["postType","location",{search:v,per_page:100,context:"view",orderby:"relevance"}];return{locations:e(n.store).getEntityRecords(...t),locationResolved:e(n.store).hasFinishedResolution("getEntityRecords",t)}}),[v]),C=function(){if(!R)return(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(s.Spinner,{}),(0,i.jsx)("br",{})]});if(""==v||null==v)return"";if(!b?.length)return(0,i.jsxs)("div",{children:[" ",(0,t.__)("No location found","sim")]});let e=b.map((e=>({label:e.title.rendered,value:e.id})));return(0,i.jsx)(i.Fragment,{children:(0,i.jsx)(s.RadioControl,{selected:parseInt(x.location_id),options:e,onChange:t=>m("location_id",t,e.filter((e=>t==e.value))[0].label)})})},k=function(){if(!y)return(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(s.Spinner,{}),(0,i.jsx)("br",{})]});if(""==_||null==_)return"";if(!f?.length)return(0,i.jsxs)("div",{children:[" ",(0,t.__)("No user found","sim")]});let e=f.map((e=>({label:e.name,value:e.id})));return(0,i.jsx)(i.Fragment,{children:(0,i.jsx)(s.RadioControl,{selected:parseInt(x.organizer_id),options:e,onChange:t=>m("organizer_id",t,e.filter((e=>t==e.value))[0].label)})})},w=()=>null==x.repeat.excludedates?"":x.repeat.excludedates.map(((e,t)=>(0,i.jsx)(s.DatePicker,{currentDate:x.repeat.excludedates[t],value:e,onChange:e=>{m("repeat-excludedates",e,t)}}))),S=()=>"daily"!=x.repeat.type?"":(0,i.jsxs)(i.Fragment,{children:[(0,t.__)("Repeat interval in days"),(0,i.jsx)(s.__experimentalNumberControl,{onChange:e=>m("repeat-interval",e),value:x.repeat.interval}),(0,i.jsx)("br",{})]}),D=()=>{if("weekly"!=x.repeat.type)return"";null==x.repeat.weeks&&(x.repeat.weeks=[]);let e=["First","Second","Third","Fourth","Fifth","All"].map((e=>(0,i.jsx)(s.CheckboxControl,{label:(0,t.__)(e),checked:x.repeat.weeks.includes(e),onChange:t=>m("repeat-weeks",t,e)})));return(0,i.jsxs)(i.Fragment,{children:[(0,t.__)("Repeat interval in weeks"),(0,i.jsx)(s.__experimentalNumberControl,{onChange:e=>m("repeat-interval",e),value:x.repeat.interval}),(0,i.jsx)("br",{}),(0,t.__)("Select weeks(s) of the month this event should be repeated on"),(0,i.jsx)("br",{}),(0,i.jsx)("div",{class:"flex week-selector",children:e}),(0,i.jsx)("br",{})]})},F=()=>{if("monthly"!=x.repeat.type)return"";null==x.repeat.months&&(x.repeat.months=[]);let e=new Date(x.startdate),r=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"][e.getDay()],a=parseInt(e.getDate()/7);console.log(a);let n=["first","second","third","fourth","fifth"][a];return(0,i.jsxs)(i.Fragment,{children:[(0,t.__)("Select repeat type"),(0,i.jsx)("br",{}),(0,i.jsxs)(s.__experimentalRadioGroup,{label:"Width",onChange:e=>m("repeat-datetype",e),checked:x.repeat.datetype,children:[(0,i.jsx)(s.__experimentalRadio,{value:"samedate",children:(0,t.__)("On the same day")}),(0,i.jsx)(s.__experimentalRadio,{value:"patterned",children:(0,t.__)(`On the ${n} ${r} of the month`)}),(0,i.jsx)(s.__experimentalRadio,{value:"last",children:(0,t.__)(`On the last ${r} of the month`)}),(0,i.jsx)(s.__experimentalRadio,{value:"lastday",children:(0,t.__)("On the last day of the month")})]}),(0,i.jsx)("br",{}),(0,i.jsx)("br",{}),(0,i.jsx)(T,{})]})},T=()=>{if(null==x.repeat.datetype)return"";if("samedate"==x.repeat.datetype)return(0,i.jsxs)(i.Fragment,{children:[(0,t.__)("Repeat interval in months"),(0,i.jsx)(s.__experimentalNumberControl,{onChange:e=>m("repeat-interval",e),value:x.repeat.interval}),(0,i.jsx)("br",{})]});let e=["All","January","February","March","April","May","June","July","August","September","October","November","December"].map((e=>(0,i.jsx)(s.CheckboxControl,{label:(0,t.__)(e),checked:x.repeat.months.includes(e),onChange:t=>m("repeat-months",t,e)}))),r=e.splice(0,1),a=e.splice(0,6);return(0,i.jsxs)(i.Fragment,{children:[(0,t.__)("Select month(s) this event should be repeated"),(0,i.jsx)("br",{}),(0,i.jsx)("div",{class:"flex month-selector",children:r}),(0,i.jsx)("div",{class:"flex month-selector",children:a}),(0,i.jsx)("div",{class:"flex month-selector",children:e}),(0,i.jsx)("br",{})]})},O=()=>{if("custom_days"!=x.repeat.type)return"";let e=(0,i.jsx)(s.DatePicker,{currentDate:null,onChange:e=>{m("repeat-includedates",e)}});return null==x.repeat.includedates?e:(0,i.jsxs)(i.Fragment,{children:[x.repeat.includedates.map(((e,t)=>(0,i.jsx)(s.DatePicker,{currentDate:x.repeat.includedates[t],value:e,onChange:e=>{m("repeat-includedates",e,t)}}))),e,";"]})},B=()=>{if(null==x.startdate)return"";const e=()=>{if(x.allday)return"";let e=null==x.starttime?"00:00:00":x.starttime,r=null==x.endtime?"00:00:00":x.endtime;return(0,i.jsxs)(i.Fragment,{children:[(0,i.jsxs)("div",{children:[(0,i.jsx)("span",{class:"center",children:(0,t.__)("Start time","sim")}),(0,i.jsx)(s.TimePicker,{currentTime:"1986-10-18T"+e,onChange:e=>m("starttime",e),__nextRemoveHelpButton:!0,__nextRemoveResetButton:!0})]}),(0,i.jsxs)("div",{children:[(0,i.jsx)("span",{class:"center",children:(0,t.__)("End time","sim")}),(0,i.jsx)(s.TimePicker,{currentTime:"1986-10-18T"+r,onChange:e=>m("endtime",e),__nextRemoveHelpButton:!0,__nextRemoveResetButton:!0})]})]})};return(0,i.jsx)(i.Fragment,{children:(0,i.jsxs)("div",{class:"time-pickers flex",children:[(0,i.jsx)(e,{}),(0,i.jsx)(s.CheckboxControl,{label:(0,t.__)("This is an all day event"),onChange:e=>m("allday",e),checked:x.allday})]})})};return(0,i.jsxs)("div",{...d,children:[(0,i.jsx)("h2",{children:(0,t.__)("Event Details")}),(0,i.jsxs)("div",{class:"date-pickers flex",children:[(0,i.jsxs)("div",{children:[(0,i.jsx)("span",{class:"center",children:(0,t.__)("Start date","sim")}),(0,i.jsx)(s.DatePicker,{currentDate:x.startdate?x.startdate:null,onChange:e=>m("startdate",e),__nextRemoveHelpButton:!0,__nextRemoveResetButton:!0})]}),(0,i.jsxs)("div",{children:[(0,i.jsx)("span",{class:"center",children:(0,t.__)("End date","sim")}),(0,i.jsx)(s.DatePicker,{currentDate:x.enddate?x.enddate:null,onChange:e=>m("enddate",e),__nextRemoveHelpButton:!0,__nextRemoveResetButton:!0})]})]}),(0,i.jsx)(B,{}),null==x.endtime&&(null==x.allday||!x.allday)||x.enddate==x.startdate&&x.endtime<=x.starttime&&(null==x.allday||!x.allday)?"":(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)("i",{children:(0,t.__)("Use searchbox below to search for a location","sim")}),(0,i.jsx)(s.SearchControl,{onChange:g,value:v}),(0,i.jsx)(C,{}),(0,i.jsx)("br",{}),(0,i.jsx)("i",{children:(0,t.__)("Use searchbox below to search for an user to add as organizer","sim")}),(0,i.jsx)(s.SearchControl,{onChange:j,value:_}),(0,i.jsx)(k,{}),(0,i.jsx)("br",{}),(0,i.jsx)(s.Button,{variant:"primary",className:"repeat-button",onClick:e=>m("isrepeated",!x.isrepeated),children:"Repeat this event"}),(0,i.jsx)("br",{}),null!=x.isrepeated&&x.isrepeated?(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)("br",{}),(0,i.jsxs)("div",{id:"event-repeat-details",children:[(0,t.__)("Select repetition type"),(0,i.jsx)("br",{}),(0,i.jsxs)(s.__experimentalRadioGroup,{label:"Width",onChange:e=>m("repeat-type",e),checked:x.repeat.type,children:[(0,i.jsx)(s.__experimentalRadio,{value:"daily",children:"Daily"}),(0,i.jsx)(s.__experimentalRadio,{value:"weekly",children:"Weekly"}),(0,i.jsx)(s.__experimentalRadio,{value:"monthly",children:"Monthly"}),(0,i.jsx)(s.__experimentalRadio,{value:"yearly",children:"Yearly"}),(0,i.jsx)(s.__experimentalRadio,{value:"custom_days",children:"Custom Days"})]}),(0,i.jsx)("br",{}),(0,i.jsx)("br",{}),(0,i.jsx)(S,{}),(0,i.jsx)(D,{}),(0,i.jsx)(F,{}),(0,i.jsx)(O,{}),(0,t.__)("Stop repetition"),(0,i.jsx)(s.RadioControl,{selected:x.repeat.stop,options:[{label:"Never",value:"never"},{label:"On this date",value:"date"},{label:"After this amount of repeats",value:"after"}],onChange:e=>m("repeat-stop",e)}),null==x.repeat.stop||"never"==x.repeat.stop?"":"date"==x.repeat.stop?(0,i.jsxs)(i.Fragment,{children:[(0,t.__)("Last date"),(0,i.jsx)(s.DatePicker,{currentDate:x.repeat.enddate,onChange:e=>m("repeat-enddate",e),__nextRemoveHelpButton:!0,__nextRemoveResetButton:!0})]}):(0,i.jsxs)(i.Fragment,{children:[(0,t.__)("Maximum occurences"),(0,i.jsx)(s.__experimentalNumberControl,{onChange:e=>m("repeat-amount",e),value:x.repeat.amount}),(0,i.jsx)("br",{})]}),(0,i.jsx)("br",{}),(0,t.__)("Exclude dates, click to select or deselect"),(0,i.jsx)(w,{}),(0,i.jsx)(s.DatePicker,{currentDate:null,onChange:e=>m("repeat-excludedates",e,0),__nextRemoveHelpButton:!0,__nextRemoveResetButton:!0})]})]}):""]})]})},save:()=>null})}},r={};function a(e){var n=r[e];if(void 0!==n)return n.exports;var s=r[e]={exports:{}};return t[e](s,s.exports,a),s.exports}a.m=t,e=[],a.O=(t,r,n,s)=>{if(!r){var l=1/0;for(c=0;c<e.length;c++){r=e[c][0],n=e[c][1],s=e[c][2];for(var i=!0,o=0;o<r.length;o++)(!1&s||l>=s)&&Object.keys(a.O).every((e=>a.O[e](r[o])))?r.splice(o--,1):(i=!1,s<l&&(l=s));if(i){e.splice(c--,1);var d=n();void 0!==d&&(t=d)}}return t}s=s||0;for(var c=e.length;c>0&&e[c-1][2]>s;c--)e[c]=e[c-1];e[c]=[r,n,s]},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={57:0,350:0};a.O.j=t=>0===e[t];var t=(t,r)=>{var n,s,l=r[0],i=r[1],o=r[2],d=0;if(l.some((t=>0!==e[t]))){for(n in i)a.o(i,n)&&(a.m[n]=i[n]);if(o)var c=o(a)}for(t&&t(r);d<l.length;d++)s=l[d],a.o(e,s)&&e[s]&&e[s][0](),e[s]=0;return a.O(c)},r=self.webpackChunksim_pendingpages=self.webpackChunksim_pendingpages||[];r.forEach(t.bind(null,0)),r.push=t.bind(null,r.push.bind(r))})();var n=a.O(void 0,[350],(()=>a(825)));n=a.O(n)})();