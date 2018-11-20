/*
 * DO_Planningtool. 
 *
 * Plugin Name:         DO_Planningtool
 * Plugin URI:          https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/
 * Description:         De mogelijkheid om video's in te voegen met diverse media-formats en ondertitels
 * Version:             1.0.0
 * Version description: First version
 * Author:              Paul van Buuren
 * Author URI:          https://wbvb.nl
 * License:             GPL-2.0+
 */


function AddTableARIA() {
  // in order to have table-like functionality for AT, add appropriate roles to table elements
  // HT to Adrian Roselli
  // see: adrianroselli.com/2018/05/functions-to-add-aria-to-tables-and-lists.html
  try {
    var i = 0;
    var allTables = document.querySelectorAll('table');
    for ( i = 0; i < allTables.length; i++) {
      allTables[i].setAttribute('role','table');
    }
    var allRowGroups = document.querySelectorAll('thead, tbody, tfoot');
    for ( i = 0; i < allRowGroups.length; i++) {
      allRowGroups[i].setAttribute('role','rowgroup');
    }
    var allRows = document.querySelectorAll('tr');
    for ( i = 0; i < allRows.length; i++) {
      allRows[i].setAttribute('role','row');
    }
    var allCells = document.querySelectorAll('td');
    for ( i = 0; i < allCells.length; i++) {
      allCells[i].setAttribute('role','cell');
    }
    var allHeaders = document.querySelectorAll('th');
    for ( i = 0; i < allHeaders.length; i++) {
      allHeaders[i].setAttribute('role','columnheader');
    }
    // this accounts for scoped row headers
    var allRowHeaders = document.querySelectorAll('th[scope=row]');
    for ( i = 0; i < allRowHeaders.length; i++) {
      allRowHeaders[i].setAttribute('role','rowheader');
    }
    // caption role not needed as it is not a real role and
    // browsers do not dump their own role with display block
  } catch (e) {
    console.log("Woeps, AddTableARIA(): " + e);
  }


}

AddTableARIA();



function letDivsScroll() {

  try {
    var i = 0;
    var allProgrammaDivs = document.querySelectorAll('div.programma');
    for ( i = 0; i < allProgrammaDivs.length; i++) {
      
      console.log('width: ' + allProgrammaDivs[i].dataset.possiblewidth );

      allProgrammaDivs[i].addEventListener('scroll', stickyTimescaleblock);
      allProgrammaDivs[i].style.width = allProgrammaDivs[i].dataset.possiblewidth;
      allProgrammaDivs[i].classList.add('fixed');

    }
  } catch (e) {
    console.log("Woeps, AddTableARIA(): " + e);
  }

}


//var nav     = document.querySelector('#timescaleblock_1');
//var nav     = document.querySelector('#timescaleblock_1');
//var navTop  = nav.offsetTop;
//var navLeft = nav.offsetLeft;
//var sLeft   = element.scrollLeft;

function stickyTimescaleblock( e ) {

  var sLeft   = e.scrollLeft;
  var nav     = e.querySelector('.timescaleblock');

  console.log('sLeft = ' + sLeft);
//  console.log('navLeft = ' + navLeft);
//  console.log('scrollY = ' + window.scrollY);
//  console.log('scrollX = ' + window.scrollX);

  if (window.scrollY >= navTop) {
    // nav offsetHeight = height of nav
//    document.body.style.paddingTop = nav.offsetHeight + 'px';
    nav.classList.add('fixed');
  } else {
//    document.body.style.paddingTop = 0;
    nav.classList.remove('fixed');
  }
}

//window.addEventListener('scroll', stickyTimescaleblock);


letDivsScroll();
