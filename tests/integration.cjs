/* Real HTTP + MySQL integration test. Creates and drops only its random test database. */
const {spawn,spawnSync}=require('node:child_process');
const {randomBytes}=require('node:crypto');
const assert=require('node:assert/strict');
const path=require('node:path');
const fs=require('node:fs');
const root=path.resolve(__dirname,'..');
const php=process.env.PHP_BINARY||'C:\\xampp\\php\\php.exe';
const name='bh_test_'+randomBytes(6).toString('hex');
const secret=randomBytes(16).toString('hex');
const port=18900+Math.floor(Math.random()*500);
const env={...process.env,BH_DB_NAME:name,BH_ADMIN_EMAIL:'admin@example.test',BH_ADMIN_PASSWORD:secret};
let server,checks=0,uploaded;
function check(value,message){assert.ok(value,message);checks++;console.log('PASS '+message);}
function run(file,args=[]){const r=spawnSync(php,[file,...args],{cwd:root,env,encoding:'utf8'});if(r.status!==0)throw new Error(r.stderr+r.stdout);return r.stdout;}
function client(){let cookie='',csrf='';return {async call(action,data=null,params={},expected=200,token=true){
 const headers={Cookie:cookie};const options={headers};
 if(data!==null){options.method='POST';if(token)headers['X-CSRF-Token']=csrf;if(data instanceof FormData)options.body=data;else{headers['Content-Type']='application/json';options.body=JSON.stringify(data);}}
 const response=await fetch(`http://127.0.0.1:${port}/api.php?`+new URLSearchParams({action,...params}),options);
 const set=response.headers.get('set-cookie');if(set)cookie=set.split(';')[0];
 const body=await response.json();assert.equal(response.status,expected,`${action}: ${JSON.stringify(body)}`);if(body.csrf)csrf=body.csrf;return body;
}};}
async function main(){
 run('setup.php');server=spawn(php,['-S',`127.0.0.1:${port}`,'router.php'],{cwd:root,env,stdio:'ignore',windowsHide:true});
 for(let i=0;i<40;i++){try{await fetch(`http://127.0.0.1:${port}/`);break;}catch{await new Promise(r=>setTimeout(r,150));}}
 const owner=client(),renter=client(),other=client(),admin=client(),guest=client();
 for(const c of [owner,renter,other,admin,guest])await c.call('session');
 await guest.call('contact',{name:'Bad actor',email:'bad@example.test',subject:'Test',body:'No csrf here'}, {},403,false);check(true,'Mutations reject missing CSRF tokens');
 await guest.call('rentals',null,{},401);await guest.call('admin',null,{},401);check(true,'Private rentals and admin require authentication');
 await owner.call('register',{name:'Test Owner',email:'owner@example.test',city:'Colombo',password:secret},{},201);
 await renter.call('register',{name:'Test Borrower',email:'renter@example.test',city:'Kandy',password:secret},{},201);
 await other.call('register',{name:'Other Member',email:'other@example.test',city:'Galle',password:secret},{},201);
 await admin.call('login',{email:'admin@example.test',password:secret});check(true,'Registration and admin login succeed');
 await renter.call('admin',null,{},403);check(true,'Members cannot access admin records');
 const cats=await guest.call('categories');check(cats.length===6,'Six categories installed');
 const listing={title:'Test camera <script>',description:'A camera used to test a complete rental lifecycle.',category_id:cats[0].id,city:'Colombo',daily_rate:'250.50',deposit:'1000',item_condition:'Good',available:'1'};
 const created=await owner.call('item_save',listing);const itemId=created.id;check(!!itemId,'Owner can create a listing');
 const today=(await guest.call('session')).today;
 const booking={item_id:itemId,start_date:today,end_date:today,note:'Please include the charger.'};
 await owner.call('book',booking,{},400);check(true,'Owners cannot rent their own items');
 await renter.call('book',{...booking,start_date:'2026-02-30'}, {},400);check(true,'Invalid calendar dates rejected');
 await renter.call('item_save',{...listing,id:itemId}, {},403);await renter.call('item_delete',{id:itemId},{},403);check(true,'Listing edits and removal enforce ownership');
 const b1=await renter.call('book',booking,{},201);await renter.call('book',booking,{},409);const b2=await other.call('book',booking,{},201);check(true,'Duplicate pending requests rejected; different members may request');
 await renter.call('rental_status',{id:b1.id,status:'accepted'},{},409);check(true,'Borrower cannot approve their own request');
 await owner.call('rental_status',{id:b1.id,status:'accepted'});const competing=await other.call('rentals');check(competing[0].status==='rejected','Approval declines overlapping pending requests');
 await other.call('book',booking,{},409);check(true,'Confirmed dates cannot be double booked');
 await owner.call('item_delete',{id:itemId},{},409);check(true,'Confirmed rentals block listing removal');
 await owner.call('item_save',{...listing,id:itemId,daily_rate:'999'});const booked=await renter.call('rentals');check(Number(booked[0].total)===250.5,'Booking price is preserved after listing price changes');
 await other.call('messages',null,{id:b1.id},403);await other.call('message_send',{id:b1.id,body:'Intrusion'},{},403);check(true,'Messages are private to the two rental participants');
 await renter.call('message_send',{id:b1.id,body:'Hello, where can I collect?'},{},201);const msgs=await owner.call('messages',null,{id:b1.id});check(msgs.length===1,'Participants can exchange messages');
 await renter.call('review',{id:b1.id,rating:5,comment:'Early review'},{},403);check(true,'Reviews require a completed rental');
 await owner.call('rental_status',{id:b1.id,status:'active'});await renter.call('rental_status',{id:b1.id,status:'completed'},{},409);await renter.call('rental_status',{id:b1.id,status:'return_requested'});await owner.call('rental_status',{id:b1.id,status:'completed'});check(true,'Pickup and two-party return lifecycle works');
 await renter.call('review',{id:b1.id,rating:5,comment:'Everything worked well.'},{},201);await renter.call('review',{id:b1.id,rating:5,comment:'Duplicate'},{},409);check(true,'One review per completed rental');
 const item=await guest.call('item',null,{id:itemId});check(item.reviews.length===1 && item.title===listing.title,'Public item displays stored content and completed review');
 const filter=await guest.call('items',null,{q:'Test camera',city:'Colombo',max:'1000'});check(filter.total===1,'Search, location and price filters work');
 await admin.call('admin_item',{id:itemId,hidden:1});await guest.call('item',null,{id:itemId},404);check((await guest.call('items')).total===0,'Admin-hidden listings are removed from public results');await admin.call('admin_item',{id:itemId,hidden:0});
 const fd=new FormData();Object.entries({...listing,title:'Uploaded image',id:itemId}).forEach(([k,v])=>fd.append(k,String(v)));fd.append('image',new Blob([Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg==','base64')],{type:'image/png'}),'photo.png');await owner.call('item_save',fd);uploaded=(await owner.call('item',null,{id:itemId})).image;check(/^[a-f0-9]{36}\.png$/.test(uploaded),'Valid uploads receive randomized safe names');
 const bad=new FormData();Object.entries(listing).forEach(([k,v])=>bad.append(k,String(v)));bad.append('image',new Blob(['<?php echo 1; ?>'],{type:'image/png'}),'bad.png');await owner.call('item_save',bad,{},400);check(true,'Executable content disguised as an image is rejected');
 await guest.call('contact',{name:'Test Guest',email:'guest@example.test',subject:'Help with a rental',body:'Please help me with my rental question.'},{},201);const data=await admin.call('admin');check(data.contacts.length===1,'Contact form submissions reach the admin inbox');await admin.call('admin_contact',{id:data.contacts[0].id});check(Number((await admin.call('admin')).contacts[0].resolved)===1,'Admin can resolve contact messages');
 await admin.call('admin_category',{name:'Garden equipment'});check((await guest.call('categories')).length===7,'Admin can add categories');
 const otherUser=(await other.call('session')).user;await admin.call('admin_user',{id:otherUser.id,active:0});await other.call('rentals',null,{},401);check(true,'Suspension invalidates a member’s active session');
 await renter.call('profile',{name:'Updated Borrower',city:'Galle',phone:'+94 77 123 4567'});check((await renter.call('session')).user.city==='Galle','Profile changes persist');
 await renter.call('password',{current_password:'wrong-password',password:secret+'x'},{},400);await renter.call('password',{current_password:secret,password:secret+'x'});await renter.call('logout',{});await renter.call('login',{email:'renter@example.test',password:secret+'x'});check(true,'Password change requires current password and updates login');
 const out=run('reset-password.php',['renter@example.test']);const token=out.match(/\b[a-f0-9]{64}\b/)[0];await guest.call('reset',{token,password:secret+'y'});await renter.call('rentals',null,{},401);await guest.call('reset',{token,password:secret+'z'},{},400);check(true,'Password reset is one-time and invalidates old sessions');
 await owner.call('item_delete',{id:itemId});await guest.call('item',null,{id:itemId},404);check((await owner.call('rentals')).length===2,'Removing listings preserves rental history');
 for(const p of ['/config.php','/database/schema.sql','/tests/integration.cjs','/.htaccess']){const r=await fetch(`http://127.0.0.1:${port}${p}`);assert.equal(r.status,404);}check(true,'Development router protects private project files');
 console.log(`\n${checks} integration checks passed against MySQL (${name}).`);
}
main().catch(e=>{console.error(e);process.exitCode=1;}).finally(()=>{
 if(server)server.kill();if(uploaded)fs.unlinkSync(path.join(root,'uploads',uploaded));
 // Only the generated test database can be removed here.
 if(/^bh_test_[a-f0-9]{12}$/.test(name))spawnSync(php,['-r',`require 'lib.php'; db()->exec('DROP DATABASE ${name}');`],{cwd:root,env,stdio:'inherit'});
});
