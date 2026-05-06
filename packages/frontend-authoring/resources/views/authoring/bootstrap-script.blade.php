window.CapellFrontendAuthoring = window.CapellFrontendAuthoring || (function ()
{ function closeModal() { document .querySelectorAll('.capell-authoring-modal')
.forEach((modal) => modal.remove()) } function openModal(region) { closeModal()
const overlay = document.createElement('div') overlay.className =
'capell-authoring-modal' overlay.innerHTML = `
<div class="capell-authoring-modal__backdrop"></div>
<div
    class="capell-authoring-modal__panel"
    role="dialog"
    aria-modal="true"
    aria-label="${region.label}"
>
    <button
        class="capell-authoring-modal__close"
        type="button"
        aria-label="Close"
    >
        x
    </button>
    <iframe
        class="capell-authoring-modal__frame"
        src="${region.edit_url}"
        title="${region.label}"
    ></iframe>
</div>
` document.body.appendChild(overlay) overlay
.querySelector('.capell-authoring-modal__close') .addEventListener('click',
closeModal) overlay .querySelector('.capell-authoring-modal__backdrop')
.addEventListener('click', closeModal) } function ensureStyles() { if
(document.getElementById('capell-authoring-styles')) { return } const style =
document.createElement('style') style.id = 'capell-authoring-styles'
style.textContent = ` .capell-authoring-region { outline: 2px dashed rgba(37,
99, 235, .65); outline-offset: 4px; position: relative; }
.capell-authoring-button { background: #111827; border: 0; border-radius: 999px;
color: #fff; cursor: pointer; font: 600 12px/1 ui-sans-serif, system-ui,
sans-serif; padding: 7px 10px; position: absolute; right: 0; top: 0; transform:
translateY(calc(-100% - 6px)); opacity: 0; pointer-events: none; transition:
opacity .12s ease, transform .12s ease; z-index: 2147483000; }
.capell-authoring-region:hover > .capell-authoring-button,
.capell-authoring-region:focus-within > .capell-authoring-button,
.capell-authoring-button:focus { opacity: 1; pointer-events: auto; }
.capell-authoring-modal { inset: 0; position: fixed; z-index: 2147483001; }
.capell-authoring-modal__backdrop { background: rgba(17, 24, 39, .55); inset: 0;
position: absolute; } .capell-authoring-modal__panel { background: #fff;
border-radius: 12px; box-shadow: 0 24px 80px rgba(15, 23, 42, .3); height:
min(560px, calc(100vh - 32px)); left: 50%; max-width: calc(100vw - 32px);
overflow: hidden; position: absolute; top: 50%; transform: translate(-50%,
-50%); width: min(760px, calc(100vw - 32px)); } .capell-authoring-modal__close {
align-items: center; background: #111827; border: 0; border-radius: 999px;
color: #fff; cursor: pointer; display: flex; font: 20px/1 ui-sans-serif,
system-ui, sans-serif; height: 32px; justify-content: center; position:
absolute; right: 10px; top: 10px; width: 32px; z-index: 2; }
.capell-authoring-modal__frame { border: 0; height: 100%; width: 100%; } `
document.head.appendChild(style) } function clearRegions() { document
.querySelectorAll('.capell-authoring-button') .forEach((button) =>
button.remove()) document .querySelectorAll('.capell-authoring-region')
.forEach((element) => element.classList.remove('capell-authoring-region'), ) }
function renderRegions(regions) { clearRegions() if (!regions || typeof regions
!== 'object') { return } ensureStyles() Object.values(regions).forEach((region)
=> { const target = document.querySelector(region.selector) if (!target ||
target.dataset.capellAuthoringRegion === region.id) { return }
target.dataset.capellAuthoringRegion = region.id
target.classList.add('capell-authoring-region') const button =
document.createElement('button') const buttonIndex =
target.querySelectorAll('.capell-authoring-button').length button.type =
'button' button.className = 'capell-authoring-button' button.textContent =
region.label button.style.transform = `translateY(calc(-100% - ${6 + buttonIndex
* 34}px))` button.addEventListener('click', (event) => { event.preventDefault()
event.stopPropagation() openModal(region) }) target.appendChild(button) }) }
window.addEventListener('message', (event) => { if (event.origin !==
window.location.origin) { return } if (event.data?.type ===
'capell-authoring:saved') { closeModal() window.location.reload() } }) return {
renderRegions, } })() window.CapellFrontendAuthoring.renderRegions(
@json($regions)
)
