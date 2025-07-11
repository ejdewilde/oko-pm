/*

$('body').append('<div style="" id="loadingDiv"></br></br>Ophalen data...<div class="loader"></div></div>');
$(window).on('load', function () {
    setTimeout(removeLoader, 500);
    //disablechecks();
});

function removeLoader() {
    $("#loadingDiv").fadeOut(500, function () {
        // fadeOut complete. Remove the loading div
        $("#loadingDiv").remove(); //makes page more lightweight 
    });
}

*/

var scripts = document.getElementsByTagName('script');
var path = scripts[scripts.length - 1].src.split('?')[0]; // remove any ?query
var mydir = path.split('/').slice(0, -1).join('/') + '/'; // remove last filename part of path
var fasen = ['voorbereiding', 'monitoring', 'terugkoppeling en dialoog', 'beleidsvorming', 'uitvoering', 'community building'];
var imgsrc = mydir + '../images/procesmonitor_cyclus.svg';
var vinksrc = mydir + '../images/vinkje.png';
var rondsrc = mydir + '../images/rondje.png';
var dburl = 'https://kansrijkeomgeving.nl/wp-content/plugins/oko-pm/getorstoredata.php';

var gid = gemeente.id;
//var gid = 26;
var gemeente = gemeente.naam;
var perc = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
//var scores = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
var stap = 0;

//haal_scores(gid);
haal_items(gid);
LaatItemsZien(0);


//haal_scores(gid);
//haal_items();

// Load SVG 1

d3.xml(imgsrc).then(function (data) {
    var FasenArr = [];
    document.getElementById("container1").appendChild(data.documentElement);
    for (i = 0; i < 6; i++) {
        var div = '#fase' + i;
        //if (i>4){div = '#fase' + i;}
        var fase = d3.select(div).attr("transform-origin", "50% 50%").attr("transform", "scale(1)");
        FasenArr.push(fase);
    }
    //var div = '#comm';
    //var fase = d3.select(div).attr("transform-origin", "50% 50%").attr("transform", "scale(1)");
    //FasenArr.push(fase);

    FasenArr.forEach(function (fileName, index) {
        FasenArr[index].on('click', function (d, i) { ToonStaps(index); })
        if (index > 4) {
            FasenArr[index]
                .on('mouseover', function (d, i) {
                    ToonTipsFasen(index);
                    //d3.select(this).style("cursor", "pointer");
                    d3.select(this)
                        .transition()
                        .duration(100)
                        .attr("transform-origin", "center")
                        .attr("transform", "scale(1.1)");
                })

        } else {
            FasenArr[index]
                .on('mouseover', function (d, i) {
                    ToonTipsFasen(index);
                    //d3.select(this).style("cursor", "pointer");
                    d3.select(this)
                        .transition()
                        .duration(100)
                        .attr("transform-origin", "center")
                        .attr("transform", "scale(1.1)");
                })
        }

        FasenArr[index]
            .on('mouseout', function (d, i) {
                d3.select(this).style("cursor", "default");
                d3.select(this)
                    .transition()
                    .duration(100)
                    .attr("transform", "scale(1)");

            });
    });
});


// Load SVG 2
// Assuming you have an SVG container with a width and height
var stappenarr = [];

var icoondiv = document.getElementById("container2");
var icoonbr = (icoondiv.offsetWidth) / 5 - 10;
icoonbr = 165;

const stappen = d3.select("#container2")
    .append("svg")
    .attr("width", icoonbr * 5 + 20)
    .attr("height", 100);

var stappendata = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
var kleuren = ["#4450c6", "#3db28f", "#fabd15", "#82cae0", "#e3032c"];

var stico =
    stappen
        .selectAll("rect#stapblok")
        .data(stappendata)
        .enter()
        .append("rect")
        .attr("id", "stapblok")
        .style("fill", function (d, i) { var num = Math.floor(i / 2); return kleuren[num]; })
        .style("cursor", "pointer")
        .attr("fill-opacity", 0.30)
        .style("stroke-width", 0)
        .style("stroke", "#011763")
        .style("stroke-alignment", "outer")
        .attr("width", icoonbr - 2)
        .attr("height", 35)
        .attr("rx", 5)
        .attr("ry", 5)
        .attr("x", function (d, i) { var num = Math.floor(i / 2); return num * icoonbr; })
        .attr("y", function (d, i) { if (isEven(i)) { return 5; } return 45; })
        .on('click', function (d, i) { ToonTipsStappen(i); ToonItems(i); })
    //.on('mouseover', function (d, i) { LichtKnoppenOp(i); d3.select(this).style("cursor", "pointer"); })
    //.on('mouseout', function (d, i) { d3.select(this).style("cursor", "default"); })
    ;
var stico2 =
    stappen
        .selectAll("rect#stapblokoverlay")
        .data(stappendata)
        .enter()
        .append("rect")
        .attr("id", "stapblokoverlay")
        .style("fill", function (d, i) { var num = Math.floor(i / 2); return kleuren[num]; })
        .attr("fill-opacity", 1)
        .style("cursor", "pointer")
        .style("stroke-width", 0)
        .attr("width", 0)
        .attr("height", 35)
        .attr("rx", 5)
        .attr("ry", 5)
        .attr("x", function (d, i) { var num = Math.floor(i / 2); return num * icoonbr; })
        .attr("y", function (d, i) { if (isEven(i)) { return 5; } return 45; })
        .on('click', function (d, i) { d3.select(this).style("stroke-width", 4); ToonTipsStappen(i); ToonItems(i); })
    //.on('mouseover', function (d, i) { LichtKnoppenOp(i); d3.select(this).style("cursor", "pointer"); })
    //.on('mouseout', function (d, i) { d3.select(this).style("cursor", "default"); })
    ;
var sticotxt = stappen
    .selectAll("text#stapnr")
    .data(stappendata)
    .enter()
    .append("text")
    .attr("pointer-events", "none")
    .attr("font-weight", "normal")
    .attr("id", "stapnr")
    .attr("fill", "black")
    .style("cursor", "pointer")
    .attr("font-size", 20)
    .attr("stroke-width", 1)
    .attr("stroke", "yellow")
    .attr("stroke-opacity", 0)
    .attr("x", function (d, i) { var num = Math.floor(i / 2); return num * icoonbr + icoonbr / 2; })
    .attr("y", function (d, i) { if (isEven(i)) { return 29; } return 69; })
    .attr("fill-opacity", 1)
    .attr("text-anchor", "middle")
    .text(function (d, i) { return i + 1; })
    .attr("fill", "black")
    ;

var vinken =
    stappen
        .selectAll("text#vinken")
        .data(perc)
        .enter()
        .append("text")
        .attr("id", "vinken")
        //.attr("xlink:href", vinksrc)
        .html("&#10004;")
        .style("cursor", "pointer")
        .attr("fill", "white")
        .attr("font-size", "20")
        .attr("text-anchor", "middle")
        .attr("width", 20)
        .attr("height", 25)
        .attr("fill-opacity", 0)
        .attr("x", function (d, i) { var num = Math.floor(i / 2); return num * icoonbr + 10; })
        .attr("y", function (d, i) { if (isEven(i)) { return 25; } return 65; });

/*
var ronden =
    stappen
        .selectAll("image")
        .data(perc)
        .enter()
        .append("image")
        .attr("id", "ronden")
        .attr("xlink:href", rondsrc)
        .attr("width", 20)
        .attr("height", 20)
        .attr("x", function (d, i) { var num = Math.floor(i / 2); return (num * icoonbr) + 15; })
        .attr("y", function (d, i) { if (isEven(i)) { return 5; } return 45; })
        .attr("fill-opacity", 0);
*/

function ToonTipsFasen(z) {

    d3.select("#faseinfo").selectAll("p").remove();
    d3.select("#faseinfo").selectAll("h3").remove();
    d3.select("#faseinfo").append("p").html(faseteksten[z]);

    stappen
        .selectAll("text")
        .transition().duration(300)
        .attr("font-size", function (i) {
            var k = i - 1;
            var num = Math.floor(k / 2);
            if (num !== z) {
                return "20";
            }
            else {
                return "25";
            }
        })
        .style("stroke-opacity", function (i) {
            var k = i - 1;
            var num = Math.floor(k / 2);
            if (num !== z) {
                return 0;
            }
            else {
                return 1;
            }
        });
    stappen
        .selectAll("text")
        .transition().delay(300).duration(300)
        .attr("font-size", function (i) {
            return "20";
        })
        .style("stroke-opacity", function (i) {
            return 0;
        });
}
function ToonTipsStappen(i) {
    d3.select("#staptitel").selectAll("p").remove();
    d3.select("#staptitel").append("p").html(staptitels[i]);
    d3.select("#intro").selectAll("h3").remove();
    d3.select("#intro").selectAll("p").remove();
    d3.select("#intro").selectAll("ul").remove();
    d3.select("#intro").selectAll("li").remove();
    d3.select("#intro").append("p").html(stapteksten[i - 1]);
    d3.select("#faseinfo").selectAll("h3").remove();
    d3.select("#faseinfo").selectAll("p").remove();
    //stico
    //    .selectAll("rect#stapblok")
    //.transition().delay(300).duration(300)
    //    .style("stroke-width", function (i) { return 3; });
}
function ToonItems(i) {
    LaatItemsZien(i);
}

function ToonStaps(n) {
    d3.select("#fasetitel").selectAll("h2").remove();
    d3.select("#fasetitel").append("h2").html(fasen[n]);

    for (let i = 1; i < 11; i++) {
        if (n === Math.floor((i - 1) / 2)) {
            makeClickable(i - 1);
        } else {
            makeUnclickable(i - 1);
            //.attr("transform", "translate(200%, 50%) scale(0.1)")
        }
        sfbalk
            .selectAll("rect")
            .attr("opacity", function (d, i) {
                if (i == n) {
                    d3.select(this).attr("opacity", 1);
                    d3.select(this).style("display", "block");
                } else {
                    d3.select(this).attr("opacity", 0);
                    d3.select(this).style("display", "none");
                }
            });
    }


}

// Function to make the image unclickable
function makeUnclickable(imageId) {
    stappenarr[imageId]
        .style("opacity", "0")
        .on("click", null)
        .style("cursor", "default");

}

// Function to make the image clickable
function makeClickable(imageId) {
    stappenarr[imageId].style("display", "block")
        .style("opacity", "1")
        .style("cursor", "pointer")
        .on("click", function () { ToonItems(imageId + 1); });
}


function isEven(number) {
    return number % 2 === 0;
}

function haal_items(gid) {
    var bijwerk = 'haalitems=nu&gid=' + gid;
    console.log(bijwerk); // dev/test only
    jQuery.ajax({
        type: "POST",
        url: dburl,
        data: bijwerk,
        timeout: 5000,
        //dataType: 'json'
    })
        .done(function (result) {
            //haal_scores(gid);
            var div = document.getElementById('items');

            div.innerHTML = result;
            //elements = document.getElementsByClassName("regular-checkbox");
            console.log('okidoki. sent response completed, items received'); // dev/test only

            //console.log(result);
        })
        .fail(function (result) {
            //console.log(url); // dev/test only
            console.log('onee. geen data. response failed met');
        });
}

function SlaOp(gid, uid, wat) {

    //var themaId = getUrlVars();
    if (wat > 0) {
        var bijwerk = 'bewaar=ja&gid=' + gid + '&uid=' + uid + '&' + jQuery('form').serialize();
    } else {
        var bijwerk = 'bewaar=ja&gid=' + gid + '&uid=' + uid;
    }
    console.log(bijwerk); // dev/test only
    jQuery.ajax({
        type: "POST",
        url: dburl,
        data: bijwerk,
        timeout: 5000,
        cache: false
    })
        .done(function (result) {
            //console.log(window.location.origin + window.location.pathname); // dev/test only
            //console.log(result);

            console.log('okidoki. sent response completed, received return result'); // dev/test only
        })
        .fail(function (result) {
            //console.log(bijwerk); // dev/test only
            console.log('onee. sent response failed');
        });
}

function haal_scores(gid) {
    var bijwerk = 'haalscores=nu&gid=' + gid;
    console.log(bijwerk);
    jQuery.ajax({
        type: "POST",
        url: dburl,
        data: bijwerk,
        timeout: 15000,
        dataType: 'json'
    })
        .done(function (result) {
            console.log('okidoki. sent response completed, scores received'); // dev/test only
            scores = result;
            //teken(thema, stap);
            //console.log(result);
        })
        .fail(function (result) {
            console.log('onee. geen scores. response failed met ');
        });
}

function LaatItemsZien(stap) {

    d3.select("#starttekst").selectAll("h2").remove();
    d3.select("#starttekst").selectAll("ul").remove();
    d3.select("#starttekst").selectAll("p").remove();
    d3.select("#kop").selectAll("h2").remove();
    d3.select("#items").selectAll("p").remove();
    d3.select("#vraag").selectAll("h3").remove();

    for (s = 1; s < 11; s++) {
        hier = 'stap_' + s;
        var x = document.getElementById(hier);
        if (s == stap) {
            x.style.display = "block";
            //d3.select("#kop").append("h2").html(stapstring[s].stap);
        } else {
            if (x !== null) {
                x.style.display = "none";
            }
        }
    }
}

function teken() {

    for (ss = 1; ss < 11; ss++) {
        perc[ss - 1] = scores[ss] / staptotalen[ss];
    }

    stico2
        .transition().duration(500)
        .attr("width", function (d, i) {
            var br = (icoonbr - 2) * perc[i];
            if (perc[i] < 1) {
                br = br - 2;
            }
            if (br < 0) { br = 0; }
            if (br > (icoonbr - 2)) { br = icoonbr - 2; }
            return br;
        })
        /*.attr("fill-opacity", function (d, i) {
            var x = 0.3;
            x = x + 0.7 * perc[i];
            return x;
        })*/
        ;
    vinken
        .transition().delay(500).duration(300)
        .attr("fill-opacity", function (d, i) {
            if (perc[i] > 0.999999) { return 1; };
            return 0;
        });
    sticotxt
        .transition().delay(500).duration(300)
        .attr("fill", function (d, i) {
            if (perc[i] > 0.52) { return "white"; };
            return "black";
        });
}

function PasAan(itemid) {
    var elem = itemid;
    var itemstuks = itemid.split("_");
    var stapdeel = itemstuks[0];//.substring(4, 1);
    var stap = stapdeel.substr(2);
    //var fase = itemstuks[1];

    if (document.getElementById(elem)) {
        if (document.getElementById(elem).checked) {
            scores[stap]++;
        } else {
            scores[stap]--;
        }
    }
    teken();

}

function SlaOp(itemid) {
    var bijwerk = 'bewaar=ja&gid=' + gid + '&uid=' + uid + '&' + jQuery('form').serialize();

    console.log(bijwerk);
    //opslaan data 	
    jQuery.ajax({
        type: "POST",
        url: dburl,
        data: bijwerk,
        timeout: 5000,
        cache: false
    })
        .done(function (result) {
            //console.log(window.location.origin + window.location.pathname); // dev/test only
            //console.log(result);
            //console.log(bijwerk); // dev/test only
            console.log('okidoki. sent response completed, received return result met '.bijwerk); // dev/test only
            PasAan(itemid);
        })
        .fail(function (result) {
            //console.log(bijwerk); // dev/test only
            console.log('onee. sent response failed met '.bijwerk);
        });
}

function tip(hier) {
    tekst = tipstring[hier];
    //wawa = 'b' + hier;
    //document.getElementById(wawa).className = "itemaccent";
    //d3.select("#tip").selectAll("h3").remove();
    d3.select("#tip").selectAll("h2").remove();
    d3.select("#tip").selectAll("p").remove();
    if (tekst) {
        d3.select("#tip").append("p").html('<b>Tip(s):</b>');
        d3.select("#tip").append("p").html(tekst);
    }
}

function ontip(hier) {
    //wawa = 'b' + hier;
    //document.getElementById(wawa).className = "textblok";
}
teken();