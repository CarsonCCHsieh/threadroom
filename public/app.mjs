import { t, setLanguage, language } from './i18n.mjs';

const $ = selector => document.querySelector(selector);
const $$ = selector => [...document.querySelectorAll(selector)];
const state = {
  health:null, busy:false, slot:'a', filter:'all', view:'original', person:{url:'/assets/model-original.png',file:null,custom:false},
  products:[
    {id:'linen',nameKey:'linen',url:'/assets/linen-shirt.png',source:'buy',sample:true,price:'68',wears:'30',currency:'USD',notes:''},
    {id:'navy',nameKey:'navy',url:'/assets/navy-overshirt.png',source:'owned',sample:true,price:'',wears:'',currency:'USD',notes:''}
  ], slots:{a:'linen',b:'navy'}, jobs:{}, objectUrls:new Set(), currentMessage:null
};
const terminalSuccess=new Set(['succeeded','completed','complete','success','done']);
const terminalFailure=new Set(['failed','error','cancelled','canceled','expired']);
const currentProduct=(slot=state.slot)=>state.products.find(p=>p.id===state.slots[slot]);
const productName=p=>p?.nameKey?t(p.nameKey):p?.name||t('customGarment');
const hasResults=()=>Object.values(state.jobs).some(job=>Boolean(job?.resultUrl));
const makeId=()=>crypto.randomUUID();
function node(tag,className,text){const el=document.createElement(tag);if(className)el.className=className;if(text!==undefined)el.textContent=text;return el;}
function notice(key,error=false){state.currentMessage={key,error};$('#message').textContent=t(key);$('#message').className=`message${error?' error':''}`;$('#message').hidden=false;}
function clearNotice(){$('#message').hidden=true;state.currentMessage=null;}
function objectUrl(blob){const url=URL.createObjectURL(blob);state.objectUrls.add(url);return url;}
function releaseUrl(url){if(state.objectUrls.has(url)){URL.revokeObjectURL(url);state.objectUrls.delete(url);}}
function currencyValue(value,currency){try{return new Intl.NumberFormat(language,{style:'currency',currency,maximumFractionDigits:2}).format(value);}catch{return `${currency} ${Number(value).toFixed(2)}`;}}
function displayPrice(p){return p.price!==''&&Number.isFinite(Number(p.price))?currencyValue(Number(p.price),p.currency):t('noPrice');}
function productImage(p,className){const image=node('img',className);image.src=p.url;image.alt=productName(p);image.loading='lazy';image.addEventListener('error',()=>{image.style.visibility='hidden';},{once:true});return image;}
function invalidateSlot(slot){delete state.jobs[slot];if(state.view==='before'&&slot===state.slot)state.view='original';}

function renderCatalog(){
  const catalog=$('#catalog');catalog.replaceChildren();
  for(const p of state.products.filter(p=>state.filter==='all'||p.source===state.filter)){
    const assigned=Object.entries(state.slots).filter(([,id])=>id===p.id).map(([slot])=>slot.toUpperCase()).join(' / ');
    const card=node('button',`product-card${assigned?' selected':''}`);card.type='button';card.setAttribute('aria-label',`${productName(p)} — ${t('chooseSlot',{slot:state.slot.toUpperCase()})}`);card.setAttribute('aria-pressed',String(state.slots[state.slot]===p.id));
    const figure=node('div','product-image');figure.append(productImage(p));if(assigned)figure.append(node('span','product-slot',assigned));figure.append(node('span','product-source',t(p.source==='owned'?'owned':'considering')));
    const meta=node('div','product-meta');meta.append(node('span','product-name',productName(p)),node('span','product-price',p.sample&&p.source==='buy'?displayPrice(p):p.source==='owned'?t('owned'):displayPrice(p)));
    card.append(figure,meta,node('p','product-category',t(p.sample?'sampleOnly':'yourPiece')));
    card.addEventListener('click',()=>{
      if(state.busy)return notice('resetFirst');
      const other=state.slot==='a'?'b':'a';
      if(state.slots[other]===p.id){[state.slots[state.slot],state.slots[other]]=[state.slots[other],state.slots[state.slot]];[state.jobs[state.slot],state.jobs[other]]=[state.jobs[other],state.jobs[state.slot]];}
      else if(state.slots[state.slot]!==p.id){state.slots[state.slot]=p.id;invalidateSlot(state.slot);}
      clearNotice();render();
    });catalog.append(card);
  }
  $$('.catalog-filters button').forEach(el=>{el.classList.toggle('active',el.dataset.filter===state.filter);el.setAttribute('aria-pressed',String(el.dataset.filter===state.filter));});
}
function renderDecision(){
  const p=currentProduct();const selected=$('#selected-product');selected.replaceChildren();if(!p)return;
  const info=node('div','selected-info');info.append(node('h3','',productName(p)),node('p','',t(p.source==='owned'?'ownedAlternativeHint':'buyHint')));selected.append(productImage(p,'selected-thumb'),info);
  $('#price').value=p.price;$('#wears').value=p.wears;$('#currency').value=p.currency;$('#notes').value=p.notes;
  for(const slot of ['a','b']){const el=$(`#select-${slot}`);el.classList.toggle('active',state.slot===slot);el.setAttribute('aria-pressed',String(state.slot===slot));const product=currentProduct(slot);el.querySelector('span:last-child').textContent=t(product?.source==='owned'?'ownAlternative':'newPossibility');}
  renderCost();
}
function renderCost(){const p=currentProduct();const price=Number(p?.price),wears=Number(p?.wears);$('#cost-value').textContent=p?.price!==''&&p?.wears!==''&&price>=0&&Number.isFinite(price)&&Number.isInteger(wears)&&wears>0?currencyValue(price/wears,p.currency)+t('wearSuffix'):'—';}
function renderHealth(){
  const health=state.health;const el=$('#connection-status');el.className=`connection-status ${health?.liveAvailable?'connected':health?'unavailable':''}`;
  el.replaceChildren(node('span','status-dot'),node('span','',t(!health?'checking':health.liveAvailable?'ready':health.configured?'serviceUnavailable':'unconfigured')));
  $('#generate').disabled=state.busy||!health?.liveAvailable;
  $('#generate span').textContent=t(state.busy?'generating':'generate');
  $('#generation-note').textContent=t(health&&!health.liveAvailable?'unconfigured':'generationNote');
}
function jobText(job){if(!job)return t('missingResult');if(job.resultUrl)return t('done');if(job.timeout)return t('jobTimeout');if(terminalFailure.has(job.status))return t('failed');return t(job.status==='queued'||job.status==='pending'?'queued':'processing');}
function renderPlaceholders(){
  for(const slot of ['a','b']){
    const job=state.jobs[slot],img=$(`#result-${slot}`),holder=$(`#placeholder-${slot}`);
    img.hidden=!job?.resultUrl;if(job?.resultUrl){img.src=job.resultUrl;img.alt=t('lookAlt',{slot:slot.toUpperCase()});holder.hidden=true;}
    else{img.removeAttribute('src');holder.hidden=false;holder.replaceChildren();const p=currentProduct(slot);if(p)holder.append(productImage(p));holder.append(node('span','',jobText(job)),node('small','',t('pendingResult')));if(job&&['queued','pending','processing','running','starting'].includes(job.status)&&!job.timeout)holder.append(node('span','loading-mark'));if(job&&!state.busy&&(job.timeout||terminalFailure.has(job.status)||job.status==='uncertain')){const retry=node('button','retry-button',t(job.id?'checkAgain':'retry'));retry.addEventListener('click',()=>retrySlot(slot));holder.append(retry);}}
  }
}
function renderStage(){
  $('#person-image').src=state.person.url;$('#person-image').alt=t('photoAlt');$('#delete-photo').hidden=!state.person.custom;$('#image-kind').textContent=t(state.view==='original'?(state.person.custom?'yourInput':'sampleInput'):'resultLabel');
  $('#photo-stage').dataset.view=state.view;
  $('#original-view').hidden=state.view!=='original';$('#compare-view').hidden=state.view!=='compare';$('#before-view').hidden=state.view!=='before';
  $$('.view-tabs button').forEach(el=>{el.classList.toggle('active',el.dataset.view===state.view);el.setAttribute('aria-pressed',String(el.dataset.view===state.view));});
  renderPlaceholders();
  if(state.view==='before'&&state.jobs[state.slot]?.resultUrl){$('#before-original').src=state.person.url;$('#after-result').src=state.jobs[state.slot].resultUrl;}
  $('#stage-caption').textContent=t(state.view==='original'?'originalCaption':'resultCaption');$('#save-card').disabled=!hasResults()||state.busy;
}
function renderJobs(){
  const el=$('#job-status');const jobs=Object.entries(state.jobs).filter(([,job])=>job);el.hidden=!jobs.length;if(!jobs.length)return;
  el.replaceChildren();for(const [slot,job]of jobs){const row=node('div','',`${slot.toUpperCase()} · ${jobText(job)}`);if(job.error)row.append(node('span','',` — ${job.error}`));if(!state.busy&&!job.resultUrl){const action=node('button','',t(job.id?'checkAgain':'retry'));action.addEventListener('click',()=>retrySlot(slot));row.append(action);}el.append(row);}
}
function render(){renderCatalog();renderDecision();renderHealth();renderStage();renderJobs();}

async function request(url,options={}){
  let response;try{response=await fetch(url,{credentials:'same-origin',cache:'no-store',...options});}catch{const e=new Error(t('networkError'));e.network=true;throw e;}
  const data=await response.json().catch(()=>({}));if(!response.ok){const e=new Error(t('serverError')+` (${response.status})`);e.status=response.status;e.serverCode=data.error?.code||data.code;e.definite=response.status>=400&&response.status<500;throw e;}return data;
}
async function checkHealth(){try{const health=await request('/api/health');state.health={configured:Boolean(health.configured),liveAvailable:Boolean(health.liveAvailable),csrf:typeof health.csrf==='string'?health.csrf:''};if(!state.health.liveAvailable)notice('notConfigured');}catch{state.health={configured:false,liveAvailable:false,csrf:''};notice('healthFailure',true);}renderHealth();}

function loadImage(source){return new Promise((resolve,reject)=>{const image=new Image();image.onload=()=>resolve(image);image.onerror=reject;image.src=source;});}
async function preparePhoto(file){
  if(!['image/jpeg','image/png','image/webp'].includes(file.type))throw new Error(t('uploadType'));if(file.size>15*1024*1024)throw new Error(t('uploadSize'));
  let image,temporary;try{if(typeof createImageBitmap==='function')image=await createImageBitmap(file,{imageOrientation:'from-image'});else{temporary=URL.createObjectURL(file);image=await loadImage(temporary);}}catch{throw new Error(t('uploadError'));}
  try{
    const sourceWidth=image.width||image.naturalWidth,sourceHeight=image.height||image.naturalHeight;if(!sourceWidth||!sourceHeight||sourceWidth*sourceHeight>60_000_000)throw new Error(t('uploadSize'));
    const scale=Math.min(1,1600/Math.max(sourceWidth,sourceHeight));const canvas=document.createElement('canvas');canvas.width=Math.max(1,Math.round(sourceWidth*scale));canvas.height=Math.max(1,Math.round(sourceHeight*scale));const ctx=canvas.getContext('2d');ctx.fillStyle='#f5f3ed';ctx.fillRect(0,0,canvas.width,canvas.height);ctx.drawImage(image,0,0,canvas.width,canvas.height);
    let blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.88));if(!blob)throw new Error(t('uploadError'));if(blob.size>2*1024*1024)blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.7));if(!blob||blob.size>4*1024*1024)throw new Error(t('uploadSize'));return new File([blob],'photo.jpg',{type:'image/jpeg'});
  }finally{image?.close?.();if(temporary)URL.revokeObjectURL(temporary);}
}
async function handlePerson(file){
  if(!file)return;if(state.busy)return notice('resetFirst');
  try{const prepared=await preparePhoto(file);releaseUrl(state.person.url);state.person={file:prepared,url:objectUrl(prepared),custom:true};state.jobs={};state.view='original';$('#consent').checked=false;$('.image-unavailable').hidden=true;$('#person-image').hidden=false;render();notice('uploadReady');}catch(e){$('#message').textContent=e.message;$('#message').className='message error';$('#message').hidden=false;}
}
async function handleGarment(file){
  if(!file)return;if(state.busy)return notice('resetFirst');
  try{const prepared=await preparePhoto(file);const owned=confirm(t('sourceQuestion'));const product={id:makeId(),name:t('customGarment'),url:objectUrl(prepared),file:prepared,source:owned?'owned':'buy',sample:false,price:'',wears:'',currency:currentProduct()?.currency||'USD',notes:''};state.products.push(product);state.slots[state.slot]=product.id;invalidateSlot(state.slot);state.filter='all';render();notice('garmentReady');}catch(e){$('#message').textContent=e.message;$('#message').className='message error';$('#message').hidden=false;}
}
async function inputFile(item){if(item.file)return item.file;const response=await fetch(item.url,{credentials:'same-origin',cache:'force-cache'});if(!response.ok)throw new Error(t('sampleUnavailable'));return preparePhoto(new File([await response.blob()],'sample.jpg',{type:response.headers.get('content-type')?.split(';')[0]||'image/jpeg'}));}
function checkResultUrl(value){if(typeof value!=='string')throw new Error(t('invalidResult'));const url=new URL(value,location.origin);if(url.origin!==location.origin||!url.pathname.startsWith('/api/')||url.username||url.password)throw new Error(t('invalidResult'));return url.href;}
function updateJob(slot,data){
  const job=state.jobs[slot];job.status=String(data.status||'processing').toLowerCase();job.timeout=false;
  if(terminalSuccess.has(job.status)){job.resultUrl=checkResultUrl(data.resultUrl);job.status='completed';job.error=null;}
  else if(terminalFailure.has(job.status)){job.error=t('jobError');}
}
const wait=ms=>new Promise(resolve=>setTimeout(resolve,ms));
async function pollJob(slot){
  const job=state.jobs[slot];if(!job?.id)return;
  for(let attempt=0;attempt<120;attempt++){
    try{const data=await request(`/api/tryons/${encodeURIComponent(job.id)}`);updateJob(slot,data);renderJobs();renderStage();if(job.resultUrl||terminalFailure.has(job.status))return;}
    catch(e){job.error=e.message;if(e.status===404||e.status===410){job.status='failed';renderJobs();renderStage();return;}job.timeout=true;renderJobs();renderStage();return;}
    await wait(10_000);
  }
  job.timeout=true;job.error=t('jobTimeout');renderJobs();renderStage();
}
async function submitSlot(slot){
  const p=currentProduct(slot);const existing=state.jobs[slot];const key=existing?.key||makeId();state.jobs[slot]={key,status:'starting',productId:p.id,error:null};renderJobs();renderStage();
  try{
    const [person,garment]=await Promise.all([inputFile(state.person),inputFile(p)]);const form=new FormData();form.append('person',person,'person.jpg');form.append('garment',garment,'garment.jpg');form.append('category','upper_body');form.append('consent','true');form.append('idempotency_key',key);
    const data=await request('/api/tryons',{method:'POST',headers:{'X-CSRF-Token':state.health.csrf},body:form});
    if(typeof data.id!=='string'&&typeof data.id!=='number')throw new Error(t('serverError'));state.jobs[slot].id=String(data.id);updateJob(slot,data);renderJobs();renderStage();if(!state.jobs[slot].resultUrl&&!terminalFailure.has(state.jobs[slot].status))await pollJob(slot);
  }catch(e){state.jobs[slot].error=e.message;state.jobs[slot].status=e.network?'uncertain':'failed';if(!e.definite&&e.network)notice('networkUnknown',true);renderJobs();renderStage();}
}
async function generate(){
  if(state.busy)return;if(!state.health?.liveAvailable)return notice('notConfigured',true);if(!$('#consent').checked)return notice('needConsent',true);if(!state.person.url)return notice('needPerson',true);if(state.slots.a===state.slots.b)return notice('needGarments',true);
  clearNotice();state.busy=true;renderHealth();renderJobs();
  try{for(const slot of ['a','b']){const job=state.jobs[slot];if(job?.resultUrl)continue;if(job?.id)await pollJob(slot);else await submitSlot(slot);}}
  finally{state.busy=false;const completed=['a','b'].filter(slot=>state.jobs[slot]?.resultUrl).length;if(completed){state.view='compare';notice(completed===2?'jobComplete':'jobPartial');}render();}
}
async function retrySlot(slot){if(state.busy)return;if(!$('#consent').checked)return notice('retryConsent',true);state.busy=true;renderHealth();try{if(state.jobs[slot]?.id)await pollJob(slot);else await submitSlot(slot);}finally{state.busy=false;if(state.jobs[slot]?.resultUrl)state.view='compare';render();}}

function download(blob,name){const url=URL.createObjectURL(blob),a=node('a');a.href=url;a.download=name;a.click();setTimeout(()=>URL.revokeObjectURL(url),30_000);}
function saveNotes(){
  const payload={app:'THREADROOM',schemaVersion:1,savedAt:new Date().toISOString(),disclaimer:t('fitDisclaimer'),looks:['a','b'].map(slot=>{const p=currentProduct(slot);const price=p.price===''?null:Number(p.price),wears=p.wears===''?null:Number(p.wears);return {look:slot.toUpperCase(),name:productName(p),source:p.source,sample:p.sample,currency:p.currency,price,expectedWears:wears,costPerWear:price!==null&&wears>0?Number((price/wears).toFixed(2)):null,notes:p.notes,hasGeneratedTryOn:Boolean(state.jobs[slot]?.resultUrl)};})};download(new Blob([JSON.stringify(payload,null,2)],{type:'application/json'}),'threadroom-notes.json');notice('noteSaved');
}
function drawCover(ctx,image,x,y,w,h){const iw=image.naturalWidth||image.width,ih=image.naturalHeight||image.height;const ratio=Math.max(w/iw,h/ih),sw=w/ratio,sh=h/ratio;ctx.drawImage(image,(iw-sw)/2,(ih-sh)/2,sw,sh,x,y,w,h);}
function wrapText(ctx,text,x,y,width,lineHeight,maxLines=3){let line='',count=0;for(const character of [...String(text)]){if(ctx.measureText(line+character).width>width&&line){ctx.fillText(line,x,y);line=character;y+=lineHeight;count++;if(count>=maxLines)return y;}else line+=character;}if(line)ctx.fillText(line,x,y);return y+lineHeight;}
async function saveCard(){
  if(!hasResults())return notice('exportNoResult');try{
    await document.fonts.ready;const canvas=document.createElement('canvas');canvas.width=1600;canvas.height=1600;const ctx=canvas.getContext('2d');ctx.fillStyle='#f5f3ed';ctx.fillRect(0,0,1600,1600);ctx.fillStyle='#242c27';ctx.font='600 35px DM, sans-serif';ctx.fillText('THREADROOM',65,85);ctx.font='400 19px DM, sans-serif';ctx.fillText(t('tagline'),65,123);
    for(const [index,slot]of ['a','b'].entries()){
      const p=currentProduct(slot),job=state.jobs[slot];const x=65+index*760;const image=await loadImage(job?.resultUrl||state.person.url);drawCover(ctx,image,x,170,710,1010);ctx.fillStyle='#f5f3ed';ctx.fillRect(x+18,190,43,43);ctx.fillStyle='#242c27';ctx.font='600 23px DM,sans-serif';ctx.fillText(slot.toUpperCase(),x+31,221);ctx.font='400 16px DM,RoomTC,RoomJP,sans-serif';ctx.fillText(t(job?.resultUrl?'resultAI':'resultOriginal'),x,1210);ctx.font='500 27px DM,RoomTC,RoomJP,sans-serif';wrapText(ctx,productName(p),x,1262,680,34,2);ctx.font='400 19px DM,RoomTC,RoomJP,sans-serif';ctx.fillText(`${displayPrice(p)} · ${t(p.source==='owned'?'owned':'considering')}`,x,1343);const price=Number(p.price),wears=Number(p.wears);if(p.price!==''&&p.wears!==''&&wears>0)ctx.fillText(`${t('costPerWear')}: ${currencyValue(price/wears,p.currency)}`,x,1377);ctx.fillStyle='#6a7364';ctx.font='400 18px DM,RoomTC,RoomJP,sans-serif';wrapText(ctx,p.notes,x,1423,680,26,2);ctx.fillStyle='#242c27';
    }
    ctx.font='400 17px DM,RoomTC,RoomJP,sans-serif';ctx.fillText(t('notSize'),65,1555);const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/png'));if(!blob)throw new Error();download(blob,'threadroom-comparison.png');notice('cardSaved');
  }catch{notice('exportError',true);}
}
async function clearSession(){
  if(state.busy)return notice('resetFirst');if(!confirm(t('clearConfirm')))return;
  for(const url of state.objectUrls)URL.revokeObjectURL(url);state.objectUrls.clear();state.person={url:'/assets/model-original.png',file:null,custom:false};state.products=state.products.filter(p=>p.sample);for(const p of state.products){p.price=p.source==='buy'?'68':'';p.wears=p.source==='buy'?'30':'';p.currency='USD';p.notes='';}state.slots={a:'linen',b:'navy'};state.jobs={};state.view='original';state.slot='a';state.filter='all';$('#consent').checked=false;$('#person-upload').value='';$('#garment-upload').value='';render();
  try{
    const cleanup=await request('/api/session',{method:'DELETE',headers:{'X-CSRF-Token':state.health?.csrf||''}});
    await checkHealth();
    if(Number(cleanup.providerCleanupManual)>0)notice('clearManual');
    else if(Number(cleanup.providerCleanupPending)>0)notice('clearPending');
    else notice('cleared');
  }catch{notice('clearFailed',true);}
}
function showInfo(kind){const dialog=$('#info-dialog');$('#dialog-title').textContent=t(`${kind}Title`);const body=$('#dialog-body');body.replaceChildren();for(let i=1;i<=(kind==='privacy'?4:3);i++)body.append(node('p','',t(`${kind}${i}`)));if(kind==='privacy'){const p=node('p');const a=node('a','',t('privacyLink'));a.href='https://www.perfectcorp.com/consumer/pages/privacy';a.target='_blank';a.rel='noopener noreferrer';p.append(a);body.append(p);}dialog.showModal();}

$('#language').addEventListener('change',event=>{setLanguage(event.target.value);render();if(state.currentMessage)notice(state.currentMessage.key,state.currentMessage.error);});
$$('.catalog-filters button').forEach(el=>el.addEventListener('click',()=>{state.filter=el.dataset.filter;renderCatalog();}));
$$('.look-switch button').forEach(el=>el.addEventListener('click',()=>{state.slot=el.dataset.slot;if(state.view==='before'&&!state.jobs[state.slot]?.resultUrl)state.view='original';render();}));
$$('.view-tabs button').forEach(el=>el.addEventListener('click',()=>{const view=el.dataset.view;if(view==='compare'&&!hasResults())return notice('needResult');if(view==='before'&&!state.jobs[state.slot]?.resultUrl)return notice('beforeNeedsResult');state.view=view;clearNotice();renderStage();}));
$('#before-slider').addEventListener('input',event=>{const value=event.target.value;$('#after-layer').style.clipPath=`inset(0 0 0 ${value}%)`;$('#slider-line').style.left=`${value}%`;});
for(const id of ['replace-person','empty-upload'])$(`#${id}`).addEventListener('click',()=>{if(state.busy)return notice('resetFirst');$('#person-upload').click();});
$('#person-upload').addEventListener('change',event=>{handlePerson(event.target.files[0]);event.target.value='';});
$('#garment-upload').addEventListener('change',event=>{handleGarment(event.target.files[0]);event.target.value='';});
$('#person-image').addEventListener('error',()=>{$('#person-image').hidden=true;$('.image-unavailable').hidden=false;});
$('#person-image').addEventListener('load',()=>{$('#person-image').hidden=false;$('.image-unavailable').hidden=true;});
$('#delete-photo').addEventListener('click',()=>{if(state.busy)return notice('resetFirst');releaseUrl(state.person.url);state.person={url:'/assets/model-original.png',file:null,custom:false};state.jobs={};state.view='original';$('#consent').checked=false;render();});
for(const id of ['price','wears','currency','notes'])$(`#${id}`).addEventListener('input',event=>{const p=currentProduct();p[id]=event.target.value;renderCost();});
$('#generate').addEventListener('click',generate);$('#save-json').addEventListener('click',saveNotes);$('#save-card').addEventListener('click',saveCard);$('#clear-session').addEventListener('click',clearSession);
$('#about-button').addEventListener('click',()=>showInfo('about'));$('#privacy-button').addEventListener('click',()=>showInfo('privacy'));$$('.dialog-close,.dialog-done').forEach(el=>el.addEventListener('click',()=>$('#info-dialog').close()));
$('#info-dialog').addEventListener('click',event=>{if(event.target===$('#info-dialog')){const rect=event.target.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)event.target.close();}});
window.addEventListener('beforeunload',()=>{for(const url of state.objectUrls)URL.revokeObjectURL(url);});
const preferred=navigator.language.toLowerCase();setLanguage(preferred.startsWith('ja')?'ja':preferred.startsWith('zh')?'zh-TW':'en');render();checkHealth();
