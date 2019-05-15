// http://www.kunalbabre.com/projects/table2CSV.php

jQuery.fn.table2CSV = function(options) {
    var options = jQuery.extend({
        separator: ';',
        header: [],
        delivery: 'file', // file, popup, value
        filename: 'output.csv'
    },
    options);

    var csvData = [];
    var headerArr = [];
    var el = this;

    //header
    var numCols = options.header.length;
    var tmpRow = []; // construct header avalible array

    if (numCols > 0) {
        for (var i = 0; i < numCols; i++) {
            tmpRow[tmpRow.length] = formatData(options.header[i]);
        }
    } else {
        //$(el).filter(':visible').find('th').each(function() {
        //    if ($(this).css('display') != 'none') tmpRow[tmpRow.length] = formatData($(this).html());
        //});
    }

    row2CSV(tmpRow);

    // actual data
    $(el).find('tr').each(function() {
        var tmpRow = [];
        //$(this).filter(':visible').find('th, td').each(function() {
        $(this).find('th, td').each(function() {
            if ($(this).css('display') != 'none') tmpRow[tmpRow.length] = formatData($(this).html());
        });
        row2CSV(tmpRow);
    });
    if (options.delivery == 'popup') {
        var mydata = csvData.join('\n');
        return popup(mydata);
    } else if (options.delivery == 'file') {
        var mydata = csvData.join('\n');

        //return download(options.filename, mydata); // does not work with firefox
        return saveTextAs(mydata, options.filename); // needs FileSaver.js
    } else {
        var mydata = csvData.join('\n');
        return mydata;
    }

    function row2CSV(tmpRow) {
        var tmp = tmpRow.join('') // to remove any blank rows
        // alert(tmp);
        if (tmpRow.length > 0 && tmp != '') {
            var mystr = tmpRow.join(options.separator);
            csvData[csvData.length] = mystr;
        }
    }
    function formatData(input) {
        // replace " with “
        var regexp = new RegExp(/["]/g);
        var output = input.replace(regexp, "“");
        //HTML
        var regexp = new RegExp(/\<[^\<]+\>/g);
        var output = output.replace(regexp, "");
        if (output == "") return '';
        return '"' + output + '"';
    }
    function popup(data) {
        var generator = window.open('', 'csv', 'height=400,width=600');
        generator.document.write('<html><head><title>CSV</title>');
        generator.document.write('</head><body >');
        generator.document.write('<textArea cols=70 rows=15 wrap="off" >');
        generator.document.write(data);
        generator.document.write('</textArea>');
        generator.document.write('</body></html>');
        generator.document.close();
        return true;
    }
    function download(filename, content) {
    var pom = document.createElement('a');
    pom.setAttribute('href', 'data:attachment/csv,charset=utf-8,' + encodeURIComponent(content));
    pom.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
    pom.setAttribute('target','_blank');
    pom.setAttribute('download', filename);
    console.log('pom', pom);
    pom.click();
}
};