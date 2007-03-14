function show_hide_column(tableID, col_no, className) {
    var table  = document.getElementById(tableID);
    var rows = table.getElementsByTagName('tr');
    var cells;
 
        console.info("total length.... %d", rows.length);
    for (var row=0; row<rows.length;row++) {
        cells = rows[row].getElementsByTagName('td');

        console.info("comparing.... %s vs %s ", cells[col_no].className, className);
        if(cells[col_no].className != className) {
            console.info("continuing.... col %d, row %d ", col_no, row);
            continue;
        }
 
        if(cells[col_no].style.display!="none"){
            cells[col_no].style.display="none";
            console.log("setting row %d to none", row);
        }else{
            cells[col_no].style.display="block";
            console.log("setting row %d to block", row);
        }
    }
} 
