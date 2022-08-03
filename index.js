$.fn.selected = function() {
    return $(this).find("option:selected").text();
}
$.fn.byValue = function(text) {
    return $(this).find("option").filter(function() {
        return $(this).text() === text;
    });
}
$.fn.selectOption = function(text) {
    return $(this).byValue(text).prop('selected', true);
}
function setDropdowns(sourceSegment, targetSegment, transition) {
    $('.search .source_segment select').val(sourceSegment);
    $('.search .target_segment select').val(targetSegment);
    $('.search .transition select').val(transition);
}
function showError(e) {
    console.error(e);

    let modal = "#error";
    let $modal = $(modal);
    $modal.modal('show');

    $(`${modal} .modal-title`).text(`Error Status ${e.status}`);
    $(`${modal} .modal-body`).html(e.responseText);
    $(`${modal} .modal-body`).find("br").remove();
}
async function $get(url, data) {
    let result = false;
    try {
        result = await $.get(url, data);
    }
    catch(e) {
        showError(e);
    }
    return result;
}
async function $getJSON(url, data) {
    let result = false;
    try {
        result = await $.getJSON(url, data);
    }
    catch(e) {
        showError(e);
    }
    return result;
}
function jsonToSelect(options, id) {
    let result = `<select id='${id}'>`;
        result += `<option></option>`;
    options.forEach(function(option) {
        let [key, val] = option;
        result += `<option value='${key}'>${val}</option>`;
    });
    result += '</select>';
    return result;
}
function jsonToOptions(options, id) {
    let result = `<option></option>`;
    options.forEach(function(option) {
        let [key, val] = option;
        result += `<option value='${key}'>${val}</option>`;
    });
    return result;
}
function jsonToDataList(options, id) {
    let result = `<input type=text name=${id} list=${id} />`;
        result += `<datalist id='${id}'>`;
    options.forEach(function(option) {
        let [key, val] = option;
        result += `<option data-id='${key}'>${val}</option>`;
    });
    result += '</datalist>';
    return result;
}
function jsonToTableRow(data, id) {
    let result = `<tr ${id}='${data['id']}'>`;
    for (let key in data) {
        if (key === 'id') continue;
        result += `<td class='${key} noverflow'>${data[key]}</td>`;
    }
    result += `</tr>`;
    return result;
}
function jsonToTable(data, id) {
    let result = ``;
    result += `<table class="table" id='${id}'>`;
    result += `<thead>`;
    result += `<tr>`;
    for (let key in data[0]) {
        if (key === 'id') continue;
        result += `<th class='sort'>${key.replace(/_/g, ' ').toTitleCase()}</th>`;
    }
    result += `<th class='submit'></th>`;
    result += `</tr>`;
    result += `</thead>`;
    result += `<tbody>`;
    for (let  i = 0; i < data.length; i++) {
        result += `<tr ${id.slice(0, id.length-1)}='${data[i]['id']}'>`;
        for (let key in data[i]) {
            if (key === 'id') continue;
            result += `<td class=${key}>${data[i][key]}</td>`;
        }
        result += `<td class='submit'><button class='ex' data-bs-id="${data[i]['id']}"></button></td>`;
        result += `</tr>`;
    }
    result += `</tbody>`;
    result += `</table>`;
    return result;
}
function download(filename, text) {
    let element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}
async function exportData(format) {
    if (!format) return false;
    let headers = {mode: 'dump', format: format};
    if (format === 'json') {
        let data = await $getJSON(url, headers);
        download("diachron.json", JSON.stringify(data));
    }
    if (format === 'sql') {
        let data = await $get(url, headers);
        download("diachron.sql", data);
    }
    if (format === 'csv') {
        let data = await $get(url, headers);
        data = data.trim();
        data = data.split("~~~~~~~");

        const zip = new JSZip();
        const folder = zip.folder("diachron");

        data.forEach(function(datum) {
            datum = datum.trim();
            datum = datum.split("\n");
            let filename = datum[0]+".csv";
            let contents = datum.slice(1).join("\n");
            if (!filename || !contents) return;
            folder.file(filename, contents);
        });

        zip.generateAsync({type:"blob"}).then(function(content) {
            saveAs(content, "diachron.zip");
        });
    }
}

function sortDropdown($sel) {
    let $options = $sel.find(`option:not(:first-child)`);
    $options.detach().sort(function(a, b) {
        let at = $(a).text();
        let bt = $(b).text();
        return (at > bt) ? 1: ((at < bt) ? -1 : 0);
    });
    $options.appendTo($sel);
}

function randomTransition() {
    let $options = $('.search .transition select').find('option');
    let $random = $options.eq(~~(Math.random() * $options.length));
    $random.prop('selected', true);
    $('.search .transition select').trigger("change");
}

function sortTables() {
    $('.sort').click(function() {
        let $table = $(this).parents("table");
        let on = $table.attr("id").slice(0, -1);
        $('.sort i').remove();
        let col = $(this).index();
        let dir = $(this).attr("dir");
        if (!dir) {
            $table.find('tbody tr').sort((a, b) => {
                return $(a).find('td').eq(col).text().localeCompare($(b).find('td').eq(col).text());
            }).appendTo($table.find('tbody'));
            $(this).attr("dir", 1);
            $(this).append(`<i class='bi bi-arrow-down'></i>`);
        }
        else if (dir == 1) {
            $table.find('tbody tr').sort((a, b) => {
                return -$(a).find('td').eq(col).text().localeCompare($(b).find('td').eq(col).text());
            }).appendTo($table.find('tbody'));
            $(this).attr("dir", -1);
            $(this).append(`<i class='bi bi-arrow-up'></i>`);
        }
        else if (dir == -1) {
            $table.find('tbody tr').sort((a, b) => {
                return $(a).attr(on) - $(b).attr(on);
            }).appendTo($table.find('tbody'));
            $(this).removeAttr("dir");
            $(this).find('i').remove();
        }
    });
}
