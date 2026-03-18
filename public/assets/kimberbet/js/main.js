let deferredPrompt;
let isCanInstallDesktop = false;

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').then(res => {
        console.log('Service Worker registered', res);
    }).catch(err => {
        console.error('Service Worker registration failed', err);
    });
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();// Prevent Chrome 67 and earlier from automatically showing the prompt
    deferredPrompt = e;// Stash the event so it can be triggered later.
    // Update UI to notify the user they can add to home screen
    isCanInstallDesktop = true;
    if(!isStopShowA2H()){
        showA2H();
    }
});

window.addEventListener('appinstalled', (evt) => {
    isCanInstall = false;
    closeHowtoA2H()
});

function removeA2H(){
    // Cookies.set('closeA2H',true,{expires:0.5/24}); //stop show element A2H 1 hour
    $('.add-home-screen-container').addClass('remove').css("display", "none");
}
function closeHowtoA2H(){
    $('.add-home-screen-container').css("display", "none");
}
function isStopShowA2H(){
    //if(Cookies.get('closeA2H')){
    $('.add-home-screen-container').css("display", "none");
    // return true; //close A2HS
    //}else{
    return false;
    //}
}

function showA2H(){
    $('.add-home-screen-container').css("display", "flex");
    $('.add-home-screen-container').css("opacity", "1");
    if(isChromeBrowser()){
        if(!isCanInstallDesktop){
            closeHowtoA2H();
        }
    }

    if (window.matchMedia('(display-mode: standalone)').matches) {
        closeHowtoA2H();
    }
}

$(document).on('click','.close-add-to-home',()=>{
    removeA2H();
});


document.querySelector('.add-home-screen-container .btn-add-to-home').addEventListener('click', () => {
    if(deferredPrompt && !isIosDevice()){
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(choiceResult => {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted A2HS');
            } else {
                console.log('User dismissed A2HS');
            }
            deferredPrompt = null;
        });
    }else{
        if(isIosDevice()){
            //is ios chrome
            $('.how-to-install-a2h-ios').addClass('active');
            if(isChrome()){
                $('.footer-content-ios-chrome').addClass('active');
            }else if(isSafari()){//is ios Safari
                $('.footer-content-ios-safari').addClass('active');
            }
        }
    }
});

$(document).on('click','.how-to-install-a2h-ios .close-how-to-install',()=>{
    $('.how-to-install-a2h-ios').removeClass('active');
})

if(!isStopShowA2H()){
    showA2H()
    touchMoveSetup('up','.add-home-screen-container',()=>{
        removeA2H();
    })
}

