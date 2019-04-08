let cleaner = undefined;
const url = "http://127.0.0.1:8000"; // TODO : get the url of the server from dynamically

const cleanerSelected = localStorage.getItem('cleanerId');
if (cleanerSelected !== undefined) {
    getCleaner(url, cleanerSelected);
    $('#js-cleaner-choice').find('#js-cleaner' + cleanerSelected).attr('selected', true);
}

$(document).ready(async () => {

    document.getElementById("js-cleaner-choice").addEventListener("change", async (e) => {
        const id = e.target.value;
        if (id === undefined || id === '')
            return;

        getCleaner(url, id);
    });


    $('.js-place-choice').click((event) => {
        if (cleaner === undefined) {
            alert('You should choose a cleaner before !');
            return;
        }
        $('.js-modal-cleaner-input').children("option[value=" + cleaner.id + "]").attr('selected', 'selected');
        $('.js-modal-place-input').children("option[value=" + $(event.target).attr('data-id') + "]").attr('selected', 'selected');

        $('.modal').addClass('is-active');
    });

    $('.js-modal-close').click(() => {
        $('.modal').removeClass('is-active');
    })

    $('#js-operation-form').submit(async (e) => {
        e.preventDefault();

        // let form = document.getElementById("js-operation-form");
        // let data = new FormData(form);

        const url = "http://127.0.0.1:8000" + "/api/operations";
        $.ajax({
            type: "POST",
            url: url,
            data: $('#js-operation-form').serialize(),
            success: (response) => {
                document.location.reload(true);
            },
        });
    })
});

function getCleaner(url, id) {
    fetch(url + "/api/cleaner/" + id, {
        method: 'get'
    })
        .then((response) => {
            return response.json()
        })
        .then((json) => {
            console.log(json);
            cleaner = json;
            localStorage.setItem('cleanerId', id);
            let ops = {
                'Monday': [], 'Tuesday': [], 'Wednesday': [], 'Thursday': [], 'Friday': [],
                'Saturday': [], 'Sunday': []
            };
            cleaner.operations.map((element) => {
                ops[element.day].push(element);
            });
            let numberOfLines = 0;
            Object.values(ops).map((elem) => numberOfLines = numberOfLines < elem.length ? elem.length : numberOfLines);

            let planning = '';

            for (let i = 0; i < numberOfLines; i++) {
                planning += "<tr>";
                Object.values(ops).map((elem) => {
                    planning +=
                        elem[i] ? "<th data-id=" + elem[i].id + ">" +
                        elem[i].place.customer.name + " - " + elem[i].place.name +
                        "</th>" : '<th></th>';
                });
                planning += "</tr>";
            }

            console.log(planning);
            $('.js-cleaner-planning-table-body').html(planning);
            console.log(ops)
            // TODO : get the table of operations and generate the HTML for all the lines.
        })
}

// TODO : filters the places with the selected customer

// TODO : filter the places with the name