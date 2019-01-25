window.onload = function () {
    const duration = $("#unix-time").text();
    const display = document.querySelector('#token-time');
    startTimer(duration, display);  
};

function startTimer(duration, display) {
    let timer = duration;
    let hours, minutes, seconds;
    let time;

    let timerId = setInterval(function () {
        
        --timer;

        hours = parseInt(timer / (60 * 60), 10);
        minutes = parseInt((timer / 60 - hours * 60), 10);
        seconds = parseInt(timer - minutes * 60, 10);

        
        hours = hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;  
        
        if(hours !== 0) {
            time = hours + "h ";
        }
        
        if (timer === 0) {
            hours = minutes = seconds = "00";
            clearInterval(timerId);
        }
        
        time = minutes + "m " + seconds + "s";

        display.textContent = time;

    }, 1000);
}

