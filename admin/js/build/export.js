(()=>{"use strict";const e=window.React,t=window.ReactDOM,s=(window.wp.hooks,window.ReactJSXRuntime),l=window.wp.components,a=window.wp.primitives,r=(0,s.jsx)(a.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,s.jsx)(a.Path,{d:"M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"})}),o=window.wp.i18n,n=Object.keys(tp.export.tables).length;let i=n+1;i=Math.max(i,3),i=Math.min(i,12);const c=tp.export.zipSupportAvailable,p=Object.entries(tp.export.tables).map((([e,t])=>(""===t.trim()&&(t=(0,o.__)("(no name)","tablepress")),{value:e,label:(0,o.sprintf)((0,o.__)("ID %1$s: %2$s","tablepress"),e,t)})));c||p.unshift({value:"",label:(0,o.__)("— Select —","tablepress"),disabled:!0});const x=Object.entries(tp.export.exportFormats).map((([e,t])=>({value:e,label:t}))),d=Object.entries(tp.export.csvDelimiters).map((([e,t])=>({value:e,label:t}))),m=()=>{var t;const a=(0,e.useRef)(null),[m,b]=(0,e.useState)({selectedTables:tp.export.selectedTables,exportFormat:tp.export.exportFormat,csvDelimiter:tp.export.csvDelimiter,createZipFile:!1,reverseList:!1}),h=m.selectedTables.length>1,_=e=>{b((t=>({...t,...e})))};return(0,e.useEffect)((()=>{a.current.size=c?i:1}),[]),(0,s.jsx)("table",{className:"tablepress-postbox-table fixed",children:(0,s.jsxs)("tbody",{children:[(0,s.jsxs)("tr",{children:[(0,s.jsx)("th",{className:"column-1 top-align",scope:"row",children:(0,s.jsxs)(l.__experimentalVStack,{spacing:"20px",children:[(0,s.jsxs)("label",{htmlFor:"tables-export-list",children:[(0,o.__)("Tables to Export","tablepress"),":"]}),c&&(0,s.jsxs)(l.__experimentalVStack,{children:[(0,s.jsx)(l.CheckboxControl,{__nextHasNoMarginBottom:!0,label:(0,o.__)("Select all","tablepress"),checked:m.selectedTables.length===n,onChange:()=>{const e=m.selectedTables.length===n?[]:Object.keys(tp.export.tables);_({selectedTables:e})}}),n>12&&(0,s.jsx)(l.CheckboxControl,{__nextHasNoMarginBottom:!0,label:(0,o.__)("Reverse list","tablepress"),checked:m.reverseList,onChange:e=>{_({reverseList:e}),p.reverse()}})]})]})}),(0,s.jsxs)("td",{className:"column-2",children:[(0,s.jsx)(l.SelectControl,{__nextHasNoMarginBottom:!0,ref:a,id:"tables-export-list",multiple:c,value:c?m.selectedTables:null!==(t=m.selectedTables[0])&&void 0!==t?t:"",onChange:e=>{"string"==typeof e&&(e=[e]),_({selectedTables:e})},options:p}),c&&(0,s.jsxs)(l.__experimentalHStack,{alignment:"left",children:[(0,s.jsx)(l.Icon,{icon:r}),(0,s.jsx)("span",{children:(0,o.sprintf)((0,o.__)("You can select multiple tables by holding down the “%1$s” key or the “%2$s” key for ranges.","tablepress"),window?.navigator?.platform?.includes("Mac")?(0,o._x)("⌘","keyboard shortcut modifier key on a Mac keyboard","tablepress"):(0,o._x)("Ctrl","keyboard key","tablepress"),(0,o._x)("Shift","keyboard key","tablepress"))})]})]})]}),(0,s.jsxs)("tr",{children:[(0,s.jsx)("th",{className:"column-1",scope:"row",children:(0,s.jsxs)("label",{htmlFor:"tables-export-format",children:[(0,o.__)("Export Format","tablepress"),":"]})}),(0,s.jsx)("td",{className:"column-2",children:(0,s.jsx)(l.__experimentalHStack,{children:(0,s.jsx)(l.SelectControl,{__nextHasNoMarginBottom:!0,id:"tables-export-format",name:"export[format]",value:m.exportFormat,label:(0,o.__)("Export Format","tablepress"),hideLabelFromVision:!0,onChange:e=>_({exportFormat:e}),options:x})})})]}),(0,s.jsxs)("tr",{children:[(0,s.jsx)("th",{className:"column-1",scope:"row",children:(0,s.jsxs)("label",{htmlFor:"tables-export-csv-delimiter",children:[(0,o.__)("CSV Delimiter","tablepress"),":"]})}),(0,s.jsx)("td",{className:"column-2",children:(0,s.jsxs)(l.__experimentalHStack,{alignment:"left",children:[(0,s.jsx)(l.SelectControl,{__nextHasNoMarginBottom:!0,id:"tables-export-csv-delimiter",name:"export[csv_delimiter]",value:m.csvDelimiter,label:(0,o.__)("CSV Delimiter","tablepress"),hideLabelFromVision:!0,onChange:e=>_({csvDelimiter:e}),options:d,disabled:"csv"!==m.exportFormat}),"csv"!==m.exportFormat&&(0,s.jsx)("span",{children:(0,o.__)("(Only needed for CSV export.)","tablepress")})]})})]}),(0,s.jsxs)("tr",{className:"bottom-border",children:[(0,s.jsxs)("th",{className:"column-1",scope:"row",children:[(0,o.__)("ZIP file","tablepress"),":"]}),(0,s.jsxs)("td",{className:"column-2",children:[tp.export.zipSupportAvailable&&(0,s.jsxs)(l.__experimentalHStack,{alignment:"left",children:[(0,s.jsx)(l.CheckboxControl,{__nextHasNoMarginBottom:!0,label:(0,o.__)("Create a ZIP archive.","tablepress"),checked:m.createZipFile||h,disabled:h,onChange:e=>_({createZipFile:e})}),h&&(0,s.jsx)("span",{children:(0,o.__)("(Mandatory if more than one table is selected.)","tablepress")})]}),!tp.export.zipSupportAvailable&&(0,o.__)("Note: Support for ZIP file creation seems not to be available on this server.","tablepress")]})]}),(0,s.jsxs)("tr",{className:"top-border",children:[(0,s.jsx)("td",{className:"column-1"}),(0,s.jsxs)("td",{className:"column-2",children:[(0,s.jsx)("input",{type:"hidden",name:"export[tables_list]",value:m.selectedTables.join()}),(0,s.jsx)("input",{type:"hidden",name:"export[zip_file]",value:m.createZipFile||h}),(0,s.jsx)(l.Button,{variant:"primary",type:"submit",disabled:0===m.selectedTables.length,text:(0,o.__)("Download Export File","tablepress")})]})]})]})})};((e,s)=>{const l=document.getElementById("tablepress-export-screen");l&&(0,t.createRoot)(l).render(s)})(0,(0,s.jsx)(m,{}))})();