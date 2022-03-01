//DOMContentLoaded start
document.addEventListener("DOMContentLoaded", function(){

    for( var i = 0; i < document.forms.length; i++ ) {

        ct_form = document.forms[i];
        d = new Date();

        ct_input = document.createElement('input');
        ct_input.setAttribute('type', 'hidden');
        ct_input.setAttribute('name', 'ct_checkjs');
        ct_input.setAttribute('class', 'ct_checkjs');
        ct_input.setAttribute('value', d.getFullYear());

        ct_form.prepend(ct_input);

    }

    var d = new Date(),
        ctTimeMs = new Date().getTime(),
        ctMouseEventTimerFlag = true, //Reading interval flag
        ctMouseData = [],
        ctMouseDataCounter = 0;

    function ctSetCookie(c_name, value) {
        document.cookie = c_name + "=" + encodeURIComponent(value) + "; path=/";
    }

    var apbctCheckJsInputs = document.getElementsByClassName("ct_checkjs");
    var apbctCheckJsInputsCount = apbctCheckJsInputs.length;
    if ( apbctCheckJsInputsCount > 0 ) {
        for ( var j = 0; j < apbctCheckJsInputsCount; j++ ) {
            apbctCheckJsInputs[j].value = d.getFullYear();
        }
    }

    ctSetCookie("ct_ps_timestamp", Math.floor(new Date().getTime()/1000));
    ctSetCookie("ct_fkp_timestamp", "0");
    ctSetCookie("ct_pointer_data", "0");
    ctSetCookie("ct_timezone", "0");
    setTimeout(function(){
        ctSetCookie("ct_timezone", d.getTimezoneOffset()/60*(-1));
    },1000);

    //Reading interval
    var ctMouseReadInterval = setInterval(function(){
        ctMouseEventTimerFlag = true;
    }, 150);

    //Writting interval
    var ctMouseWriteDataInterval = setInterval(function(){
        ctSetCookie("ct_pointer_data", JSON.stringify(ctMouseData));
    }, 1200);

    //Stop observing function
    function ctMouseStopData(){
        if(typeof window.addEventListener == "function")
            window.removeEventListener("mousemove", ctFunctionMouseMove);
        else
            window.detachEvent("onmousemove", ctFunctionMouseMove);
        clearInterval(ctMouseReadInterval);
        clearInterval(ctMouseWriteDataInterval);
    }

    //Logging mouse position each 150 ms
    var ctFunctionMouseMove = function output(event){
        if(ctMouseEventTimerFlag === true){

            ctMouseData.push([
                Math.round(event.clientY),
                Math.round(event.clientX),
                Math.round(new Date().getTime() - ctTimeMs)
            ]);

            ctMouseDataCounter++;
            ctMouseEventTimerFlag = false;
            if(ctMouseDataCounter >= 50){
                ctMouseStopData();
            }
        }
    };

    //Stop key listening function
    function ctKeyStopStopListening(){
        if(typeof window.addEventListener == "function"){
            window.removeEventListener("mousedown", ctFunctionFirstKey);
            window.removeEventListener("keydown", ctFunctionFirstKey);
        }else{
            window.detachEvent("mousedown", ctFunctionFirstKey);
            window.detachEvent("keydown", ctFunctionFirstKey);
        }
    }

    //Writing first key press timestamp
    var ctFunctionFirstKey = function output(event){
        var KeyTimestamp = Math.floor(new Date().getTime()/1000);
        ctSetCookie("ct_fkp_timestamp", KeyTimestamp);
        ctKeyStopStopListening();
    };

    if(typeof window.addEventListener == "function"){
        window.addEventListener("mousemove", ctFunctionMouseMove);
        window.addEventListener("mousedown", ctFunctionFirstKey);
        window.addEventListener("keydown", ctFunctionFirstKey);
    }else{
        window.attachEvent("onmousemove", ctFunctionMouseMove);
        window.attachEvent("mousedown", ctFunctionFirstKey);
        window.attachEvent("keydown", ctFunctionFirstKey);
    }

}); //DOMContentLoaded end
