
function runningFormatter(value, row, index) {
    return index;
}

function totalTextFormatter(data) {
    return 'Total';
}

function totalFormatter(data) {
    return data.length;
}

function extractContent(s) {
    var span = document.createElement('span');
    span.innerHTML = s;
    return span.textContent || span.innerText;
}

function extractDataTitle(s) {
    var span = document.createElement('span');
    span.innerHTML = s;
    return parseInt($(span).children().first().attr('data-title'));
}

function extractTitle(s) {
    var span = document.createElement('span');
    span.innerHTML = s;
    return parseInt($(span).children().first().attr('data-title'));
}

function extractDataPercent(s) {
    var span = document.createElement('span');
    span.innerHTML = s;
    return parseInt($(span).children().first().attr('data-percent'));
}

function timeSumFormatter(data) {
    var field = this.field;
    var ret_data = data.reduce(function(sum, row) {
        if (field === 'Duration' || field === 'TestTime' || field === 'timeRun') {
            var val = extractDataTitle(row[field]);
            return sum + (+ (val))
        }
    }, 0);
    return formatTime(ret_data);
}

function sumFormatter(data) {
    field = this.field;
    return data.reduce(function(sum, row) {
        if (field === 'PassRate') {
            var val = extractContent(row[field]);
            return sum + (+parseFloat(val));
            // }else  if (field === 'pppppppppppppppppppppp') {
            //     var val = extractContent(row[field]);
            //     return val;
        } else {
            return sum + (+ row[field]);
        }

    }, 0);
}

function avgPercentFormatter(data) {
    if (data.length > 0) {
        return Math.round(sumFormatter.call(this, data) / data.length  * 100) / 100 + '%';
    } else {
        return '0';
    }
}

function avgFormatter(data) {
    if (data.length > 0) {
        return sumFormatter.call(this, data) / data.length;
    } else {
        return '0';
    }
}

function timestrToSec(timestr) {
    var parts = timestr.split(":");
    return (parts[0] * 3600) +
        (parts[1] * 60) +
        (+parts[2]);
}

function pad(num) {
    if(num < 10) {
        return "0" + num;
    } else {
        return "" + num;
    }
}

function formatTime(seconds) {
    return [pad(Math.floor(seconds/3600)),
        pad(Math.floor(seconds/60)%60),
        pad(seconds%60),
    ].join(":");
}


/**
 * @param {string} value
 */
function statusCellStyle(value) {
    var ta = value.split(':');
    var ret = '';
    if (ta[0] > 0) {
        ret += '<div class="progress-bar progress-bar-success" style="width: '+ta[0]+'%;" title="Pass"></div>';
    }
    if (ta[1] > 0) {
        ret += '<div class="progress-bar progress-bar-warning" style="width: '+ta[1]+'%;" title="Error"></div>';
    }
    if (ta[2] > 0) {
        ret += '<div class="progress-bar progress-bar-danger" style="width: '+ta[2]+'%;" title="Fail"></div>';
    }
    if (ta[3] > 0) {
        ret += '<div class="progress-bar progress-bar-yellow" style="width: '+ta[3]+'%;" title="Warining"></div>';
    }
    if (ta[4] > 0) {
        ret += '<div class="progress-bar progress-bar-purple" style="width: '+ta[4]+'%;" title="Unknown"></div>';
    }
    if (ta[5] > 0) {
        ret += '<div class="progress-bar progress-bar-pink" style="width: '+ta[5]+'%;" title="NA"></div>';
    }
    return '<div class="progress progress-mini cycle_pb">'+ret+'</div>';
}