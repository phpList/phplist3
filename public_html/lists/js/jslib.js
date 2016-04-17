function deleteRec(url) {
    if (confirm("Are you sure you want to delete this record?")) {
        document.location = url;
    }
}

function deleteRec2(msg, url) {
    if (confirm(msg))
        document.location = url;
}


function print_self() {
    window.focus();
    if (typeof(window.print) != "undefined") {
        window.print();
    } else {
        show_print_alert();
    }
}

function show_print_alert() {
    alert("Please use your browser's print button");
}

function MM_openBrWindow(theURL, winName, features) { //v2.0
    window.open(theURL, winName, features);
}
