function htmlSorter(a, b) {
    var a = $(a).text();
    var b = $(b).text();
    if (a < b) return -1;
    if (a > b) return 1;
    return 0;
}