!function(){"use strict";let e=document.getElementById("calendarp-users"),t=document.getElementById("allow-user-add"),n=document.getElementById("allow-user");function a(e,t){return t.action=e,t.nonce=calendarpSettings.nonce,jQuery.post(ajaxurl,t)}function d(t){let n,d,i,l;n=document.createElement("li"),n.id="calendarp-"+t.id,t.link?(d=document.createElement("a"),d.href=t.link,d.innerHTML=t.name,d.title=t.linkTitle,n.appendChild(d)):(l=document.createElement("span"),l.innerHTML=t.name,n.appendChild(l)),t.removable&&(i=document.createElement("a"),i.setAttribute("data-id",t.id),i.href="#",i.className="dashicons-no-alt dashicons",i.title=calendarpSettings.removeTitle,i.addEventListener("click",(function(e){e.preventDefault(),function(e){if(confirm("Are you sure?")){let t=document.getElementById("calendarp-"+e);t&&(t.parentNode.removeChild(t),a("calendarp_remove_user",{id:e}))}}(this.getAttribute("data-id"))})),n.appendChild(i)),e.appendChild(n)}calendarpSettings.usersList.forEach(d),n&&(n.addEventListener("change",(function(){let e="hidden";this.value?t.classList?t.classList.remove(e):t.className=el.className.replace(new RegExp("(^|\\b)"+e.split(" ").join("|")+"(\\b|$)","gi")," "):t.classList?t.classList.add(e):t.className+=" "+e})),t.addEventListener("click",(function(e){e.preventDefault(),this.setAttribute("disabled","disabled"),a("calendarp_add_new_allowed_user",{id:n.value}).always((function(e){e.success&&d(e.data),t.removeAttribute("disabled")}))})))}();
//# sourceMappingURL=settings.js.map