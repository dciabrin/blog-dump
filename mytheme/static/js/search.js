var excerpt_length = 300;

// Convert a tipue index into something useful for lunr
var items = tipuesearch['pages'];
var documents = tipuesearch["pages"];
var counter = 0;
for (item in documents){
    documents[item]['id'] = counter;
    counter = counter +1;
}         
var idx = lunr(function () {
    this.ref('id');
    this.field('text', { boost: 10 });
    this.metadataWhitelist = ['position'];

    tipuesearch.pages.forEach(function (doc) {
        this.add(doc);
    }, this);
})

// Init the search widgets
$('#searchbutton').on('click', function(event) { run_search(); });
$('#searchbox').keypress(function(event) {
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            run_search();
        }
    });
$('#searchbox')[0].value="";


function run_search() {
    $('#searchresults')[0].innerHTML = '';
    var input=$('#searchbox')[0].value;
    if (input.length == 0) return;

    var results=idx.search(input);
    if(results.length>0){
        $('#searchresults').append("<p>"+results.length+" result"+(results.length>1?"s":"")+"  found...</p>");
        for (var i = 0; i < results.length; i++) {
            var ref = results[i]['ref'];
            var url = documents[ref]['url'];
            var title = documents[ref]['title'];
            var text = documents[ref]['text'];
            $('#searchresults').append("<a href=\""+url+"\">"+title+"</a>");
            var text_matches = results[i].matchData.metadata;
            var highlighted = highlight_excerpt(text, text_matches);
            $('#searchresults').append("<p class=\"excerpt\">"+highlighted+"</p>");
        }
    } else {
        $('#searchresults').append("<div class=\"noresult\"><span class=\"fa-frown-o fa\"></span><p>No result found</p></div>");
    }
}

function highlight_excerpt(text, all_matches) {
    unsorted_matches = Object.keys(all_matches).map(x => all_matches[x].text.position);
    merged_matches = [].concat.apply([], unsorted_matches);
    matches = merged_matches.sort((a,b) => a[0]>b[0]);
    var excerpt_begin=Math.max(0, text.lastIndexOf(". ",matches[0][0]));
    if (excerpt_begin>0) excerpt_begin+=1;
    if ((excerpt_begin + excerpt_length)<matches[0][0]) {
        // realign closer to the match, without expecting a dot
        excerpt_begin = text.lastIndexOf(" ", matches[0][0] - 20); // arbitrary readback
    }
    var excerpt_end=excerpt_begin + excerpt_length;
    matches = matches.filter(x => x[0] < excerpt_end);
    var tmp=[];
    tmp.push(text.substring(excerpt_begin, matches[0][0]));
    for (var i=0; i<matches.length; i++) {
        var match_end = matches[i][0] + matches[i][1];
        tmp.push("<span class=\"match\">"+text.substring(matches[i][0], match_end)+"</span>")
        var to_next_match;
        if (i+1<matches.length) {
            to_next_match=matches[i+1][0];
        } else {
            to_next_match=excerpt_end;
        }
        tmp.push(text.substring(match_end, to_next_match));
    }
    var highlighted = tmp.join('');
    return highlighted+"...";
}


