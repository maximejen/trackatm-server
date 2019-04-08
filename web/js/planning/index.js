let cleaner = undefined;
const url = "http://127.0.0.1:8000"; // TODO : get the url of the server from dynamically

const cleanerSelected = localStorage.getItem('cleanerId');
if (cleanerSelected !== undefined) {
    getCleaner(url, cleanerSelected, $('#js-operation-form').attr('data-api-token'));
    $('#js-cleaner-choice').find('#js-cleaner' + cleanerSelected).attr('selected', true);
}

$(document).ready(async () => {
    const form = $('#js-operation-form');
    const userToken = form.attr('data-api-token');
    document.getElementById("js-cleaner-choice").addEventListener("change", async (e) => {
        const id = e.target.value;
        if (id === undefined || id === '')
            return;

        getCleaner(url, id, userToken);
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

    form.submit(async (e) => {
        e.preventDefault();

        console.log(cleaner.user);
        $.ajax({
            type: "POST",
            url: "",
            headers: {
                'token': userToken
            },
            data: form.serialize(),
            success: () => {
                document.location.reload(true);
            },
            error: (error) => console.log(error)
        });
    })
});

function getCleaner(url, id, token) {
    fetch(url + "/api/cleaner/" + id, {
        method: 'get',
        headers: {
            'token': token
        }
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
                        elem[i] ? "<th data-id=" + elem[i].id + "><a href='#' data-hover='Delete'>" +
                        elem[i].place.customer.name + " - " + elem[i].place.name +
                        "</a></th>" : '<th></th>';
                });
                planning += "</tr>";
            }

            $('.js-cleaner-planning-table-body').html(planning);
        })
}