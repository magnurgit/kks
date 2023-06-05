/* Plugin Name: Magnur Trekning
 * Description: Custom Treknings-System for Budbil.no
 * Version: 1.0
 * Author: Einar Magnus Bostad
 * Author URI: www.magnur.no
 */

(function ($) {

    $(document).ready(function () {
        
        var exportPdfBtn = $('<button class="pdf-btn" type="button" id="export-pdf">Lagre som PDF</button>');
        var exportPdfBtnBud = $('<button class="pdf-btn" type="button" id="export-pdf-bud">Lagre som PDF</button>');
        var exportPdfBtnDraw = $('<button class="pdf-btn" id="export-pdf-draw">Lagre som PDF</button>');


        const contentArea = $("#content-area");

        const oversiktBtn = document.querySelector("#oversikt-btn");
        //const oversiktBudBtn = document.querySelector("#oversikt-bud-btn");
        const doDrawBtn = document.querySelector("#do-draw-btn");
        const searchInput = document.querySelector("#search-field");
        const executeSearchBtn = document.querySelector("#execute-search-btn");
        const stopBtn = document.querySelector("#stop-btn");
        const initialState = magnurTrekning.currentState;

        oversiktBtn.addEventListener("click", function () {
            $.post(magnurTrekning.ajaxurl, { action: "magnur_trekning_oversikt" }, function (response) {
                contentArea.html(response);
                contentArea.prepend(exportPdfBtn);
            });
        });

        /*
        oversiktBudBtn.addEventListener("click", function () {
            $.post(magnurTrekning.ajaxurl, { action: "magnur_trekning_oversikt_bud" }, function (response) {
                contentArea.html(response);
                contentArea.prepend(exportPdfBtnBud);
            });
        });
        */

        doDrawBtn.addEventListener("click", function () {
            Swal.fire({
                title: 'Er du sikker?',
                text: "Denne trekningen er endelig, og kan ikke kjøres på nytt. Husk å lagre trekningen som PDF før du lukker siden.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: "Avbryt",
                confirmButtonText: 'Ja, fortsett med trekningen'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(ajaxurl, { action: "do_random_draw" }, function (response) {
                        var result = JSON.parse(response);
                        displayRandomDrawResults(result);
                        contentArea.prepend(exportPdfBtnDraw);
                    });
                }
            });
        });

        searchInput.addEventListener("keypress", function (e) {
            if (e.key === "Enter" && document.activeElement === this) {
                const searchValue = document.querySelector("#search-field").value.trim();
                const searchType = document.querySelector('input[name="search-type"]:checked').value;

                if (searchType === "product") {
                    getProductByStart(searchValue);
                } else {
                    getOrderById(searchValue);
                }
            }
        });

        executeSearchBtn.addEventListener("click", function () {
            const searchValue = document.querySelector("#search-field").value.trim();
            const searchType = document.querySelector('input[name="search-type"]:checked').value;

            if (searchType === "product") {
                getProductByStart(searchValue);
            } else {
                getOrderById(searchValue);
            }
        });

        stopBtn.addEventListener("click", function () {
            const currentState = stopBtn.getAttribute("data-hide") === "yes" ? "yes" : "no";
            const newState = currentState === "yes" ? "no" : "yes";
            
            $.post(magnurTrekning.ajaxurl, {
                action: "my_plugin_set_hide_add_to_cart_buttons_option",
                state: newState
            }, function (response) {
                console.log(response);
                stopBtn.setAttribute("data-hide", newState);
                updateButtonAppearance(newState);
            });
        });
          
        contentArea.on("click", "#export-pdf", function () {
            var oversiktTable = document.getElementById("oversikt-table");
        
            // Convert the image URL to base64
            var imageUrl = "https://bcbud.no/nmkelverum/wp-content/uploads/sites/2/2023/02/NorskBilsport-Bilcross-RGB-Large.png";
            var img = new Image();
            img.src = imageUrl;
        
            img.onload = function() {
                var canvas = document.createElement("canvas");
                canvas.width = img.width;
                canvas.height = img.height;
                var ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0);
                var dataURL = canvas.toDataURL("image/png");
        
                // Create the PDF
                var pdf = new window.jspdf.jsPDF("p", "mm", "a4");
        
                // Add the image to the PDF
                var pixelToMmConversion = 0.264583;
                var imgWidth = img.width * 0.07 * pixelToMmConversion;
                var imgHeight = img.height * 0.07 * pixelToMmConversion;
                pdf.addImage(dataURL, "PNG", 10, 10, imgWidth, imgHeight);
        
                // Add the title to the PDF
                var maintitle = document.getElementById("admin-title").textContent;
                var secoundtitle = " - [Oversikt m/Total]";
                var title = maintitle.concat(secoundtitle);
                var titleFontSize = 26;
                pdf.setFontSize(titleFontSize);
                var titleX = imgWidth + 15;
                var imgCenterY = 10 + (imgHeight / 2);
                var titleY = imgCenterY + (titleFontSize / 2);
        
                // Adjust the title position (tweak this value to fine-tune the position)
                var titleAdjustment = -40; // in pixels
                titleY += titleAdjustment * pixelToMmConversion;
        
                pdf.text(title, titleX, titleY);
        
                // Add the table to the PDF
                pdf.autoTable({html: oversiktTable, 
                            theme: 'grid', 
                            startY: imgHeight + 20,
                            tableWidth: 170});
                
                addDateAndPageNumb(pdf, imgHeight);

                // Save the PDF
                pdf.save("OversiktTotal.pdf");
            };
        }); // Close the click event listener

        /*
        contentArea.on("click", "#export-pdf-bud", function () {
            // Convert the image URL to base64
            var imageUrl = "https://bcbud.no/nmkelverum/wp-content/uploads/sites/2/2023/02/NorskBilsport-Bilcross-RGB-Large.png";
            var img = new Image();
            img.src = imageUrl;
        
            img.onload = function () {
                var canvas = document.createElement("canvas");
                canvas.width = img.width;
                canvas.height = img.height;
                var ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0);
                var dataURL = canvas.toDataURL("image/png");
        
                // Create the PDF
                var pdf = new window.jspdf.jsPDF("p", "mm", "a4");
        
                // Add the image to the PDF
                var pixelToMmConversion = 0.264583;
                var imgWidth = img.width * 0.07 * pixelToMmConversion;
                var imgHeight = img.height * 0.07 * pixelToMmConversion;
                pdf.addImage(dataURL, "PNG", 10, 10, imgWidth, imgHeight);
        
                // Add the title to the PDF
                var maintitle = document.getElementById("admin-title").textContent;
                var secoundtitle = " - [Oversikt m/Bud]";
                var title = maintitle.concat(secoundtitle);
                var titleFontSize = 26;
                pdf.setFontSize(titleFontSize);
                var titleX = imgWidth + 15;
                var imgCenterY = 10 + (imgHeight / 2);
                var titleY = imgCenterY + (titleFontSize / 2);
        
                // Adjust the title position (tweak this value to fine-tune the position)
                var titleAdjustment = -40; // in pixels
                titleY += titleAdjustment * pixelToMmConversion;
        
                pdf.text(title, titleX, titleY);
        
                // Add the content to the PDF
                var h2Elements = contentArea[0].querySelectorAll("h2");
                var tableElements = contentArea[0].querySelectorAll("table");

                var startY = imgHeight + 20;
                var tableWidth = 170; // Change this value to adjust the table width
                var spaceBetweenProducts = 10; // Change this value to adjust the space between products

                for (var i = 0; i < h2Elements.length; i++) {
                    var h2Text = h2Elements[i].textContent;
                    pdf.setFontSize(14);
                    pdf.text(h2Text, 10, startY);

                    var tableElement = tableElements[i];
                    pdf.autoTable({
                        html: tableElement,
                        theme: "grid",
                        startY: startY + 5,
                        margin: { left: 10 },
                        tableWidth: tableWidth,
                        styles: { cellWidth: 'wrap' }
                    });

                    startY += pdf.previousAutoTable.finalY - startY + spaceBetweenProducts;
                }
        
                addDateAndPageNumb(pdf, imgHeight);
        
                // Save the PDF
                pdf.save("OversiktBud.pdf");
            };
        }); // Close the click event listener*/

        contentArea.on("click", "#export-pdf-draw", function () {
            
            // Convert the image URL to base64
            var imageUrl = "https://bcbud.no/nmkelverum/wp-content/uploads/sites/2/2023/02/NorskBilsport-Bilcross-RGB-Large.png";
            var img = new Image();
            img.src = imageUrl;

            img.onload = function() {
                var canvas = document.createElement("canvas");
                canvas.width = img.width;
                canvas.height = img.height;
                var ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0);
                var dataURL = canvas.toDataURL("image/png");

                // Create the PDF
                var pdf = new window.jspdf.jsPDF("p", "mm", "a4");

                // Add the image to the PDF
                var pixelToMmConversion = 0.264583;
                var imgWidth = img.width * 0.07 * pixelToMmConversion;
                var imgHeight = img.height * 0.07 * pixelToMmConversion;
                pdf.addImage(dataURL, "PNG", 10, 10, imgWidth, imgHeight);

                // Add the title to the PDF
                var maintitle = document.getElementById("admin-title").textContent;
                var secoundtitle = " - [Utfør Trekning]";
                var title = maintitle.concat(secoundtitle);
                var titleFontSize = 26;
                pdf.setFontSize(titleFontSize);
                var titleX = imgWidth + 15;
                var imgCenterY = 10 + (imgHeight / 2);
                var titleY = imgCenterY + (titleFontSize / 2);

                // Adjust the title position (tweak this value to fine-tune the position)
                var titleAdjustment = -40; // in pixels
                titleY += titleAdjustment * pixelToMmConversion;

                pdf.text(title, titleX, titleY);
        
                // Add winners list to the PDF
                var h3Elements = document.querySelectorAll("h3:not(.popup_title)");
                var pElements = document.querySelectorAll("p:not(#footer-left):not(#footer-upgrade)");

                var startY = imgHeight + 20;
                var maxPageHeight = pdf.internal.pageSize.height - 20; // Subtract some margin
                pdf.setFontSize(12);

                h3Elements.forEach(function (h3, index) {
                    var productTitle = h3.textContent;
                    var winnerText = pElements[index].textContent;

                    var textHeight = pdf.getTextDimensions(winnerText).h;
                    var requiredHeight = textHeight + 7; // Space between the product title and winner text

                    if (startY + requiredHeight > maxPageHeight) {
                        pdf.addPage();
                        startY = 20;
                    }

                    pdf.setFontSize(14); // Set the font size for the product title
                    pdf.text(productTitle, 10, startY);
                    startY += 7; // Space between the product title and winner text

                    pdf.setFontSize(12); // Set the font size for the winner text
                    pdf.text(winnerText, 10, startY);
                    startY += textHeight + 10; // Space between the winner text and the next product title
                });

        
                // Add date and page numbers
                addDateAndPageNumb(pdf, imgHeight);
        
                // Save the PDF
                pdf.save("TrekningVinnerListe.pdf");
            };
        }); // Close the click event listener
        
        function updateButtonAppearance(state) {
            const backgroundColor = state === "yes" ? "green" : "red";
            stopBtn.style.backgroundColor = backgroundColor;
            stopBtn.textContent = state === "yes" ? "Start Bud" : "Stop Bud";
        }
        

        function getProductByStart(search) {
            var productNumber = search;
        
            // Check for invalid input
            if (!productNumber || !/^\d{1,4}$/.test(productNumber)) {
                Swal.fire({
                    title: 'Ugyldig søk',
                    text: 'Beklager, men du kan kun søke på start/ordre nr. Altså kun tall fra 1 til 4 siffer.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }
        
            // Perform your search using AJAX or fetch to call your PHP function
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'search_single_product',
                    product_number: productNumber
                },
                success: function(response) {
                    if (response.trim() === 'no_results') {
                        Swal.fire({
                            title: 'Ingen treff',
                            text: 'Søket ditt gav ingen treff. Prøv på nytt.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // Parse response JSON
                        var result = JSON.parse(response);
                        
                        // Display the search result in the content-area div
                        var contentArea = document.getElementById('content-area');
                        contentArea.innerHTML = "";
        
                        // Add the product ID and title
                        var productTitle = document.createElement('p');
                        productTitle.textContent = '#' + result.productId + ' - ' + result.productTitle;
                        contentArea.appendChild(productTitle);
        
                        // Add the orders under the product
                        result.orders.forEach(function(order) {
                            var orderText = document.createElement('p');
                            orderText.textContent = '(Order Nr. ' + order.orderNr + ') ' + order.customerName + ' - Tlf: ' + order.phone + ' - Ant. Bud: ' + order.quantity + ' stk';
                            orderText.style.marginLeft = '20px';
                            contentArea.appendChild(orderText);
                        });
                    }
                },
                error: function() {
                    alert('An error occurred while searching for the product.');
                }
            });
        }
        

        function getOrderById(search) {
            var orderId = search;
        
            // Check for invalid input
            if (!orderId || !/^\d+$/.test(orderId)) {
                alert('Beklager, men du kan kun søke på ordre-nr. Ordre-nr inneholder kun tall.');
                return;
            }
        
            // Perform your search using AJAX or fetch to call your PHP function
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'search_single_order',
                    order_id: orderId
                },
                success: function(response) {
                    if (response.trim() === 'no_results') {
                        alert('Ditt søk gav ingen treff. Prøv igjen');
                    } else {
                        // Parse response JSON
                        var result = JSON.parse(response);
        
                        // Display the search result in the content-area div
                        var contentArea = document.getElementById('content-area');
                        contentArea.innerHTML = "";
        
                        // Add the order ID, customer name, and phone
                        var orderTitle = document.createElement('p');
                        orderTitle.textContent = 'Order ID: ' + result.orderId + ' - ' + result.customerName + ' - Tlf: ' + result.phone;
                        contentArea.appendChild(orderTitle);
        
                        // Add the products under the order
                        result.products.forEach(function(product) {
                            var productText = document.createElement('p');
                            productText.textContent = '#' + product.productId + ' - ' + product.productTitle + ' - Quantity: ' + product.quantity + ' stk';
                            productText.style.marginLeft = '20px';
                            contentArea.appendChild(productText);
                        });
                    }
                },
                error: function() {
                    alert('An error occurred while searching for the order.');
                }
            });
        }

        function displayRandomDrawResults(result) {
            var contentArea = document.getElementById('content-area');
            contentArea.innerHTML = "";
        
            var winnerTitle = document.createElement('h1');
            winnerTitle.textContent = "Liste over alle vinnere:";
            winnerTitle.style.textDecoration = 'underline';
            contentArea.appendChild(winnerTitle);
        
            result.forEach(function (product) {
                var productTitle = document.createElement('h3');
                productTitle.textContent = product.productId + ' - ' + product.productTitle;
                contentArea.appendChild(productTitle);
        
                var winner = product.winner;
                var winnerText = document.createElement('p');
                winnerText.innerHTML = 'Order ID ' + winner.orderId + ' - ' + winner.customerName + ' - Tlf: ' + winner.phone;
                contentArea.appendChild(winnerText);
            });
        }
        

        function getCurrentDateFormatted() {
            var now = new Date();
            var day = now.getDate();
            var month = now.getMonth() + 1;
            var year = now.getFullYear();
            var hours = now.getHours();
            var minutes = now.getMinutes();
        
            var formattedDate = (day < 10 ? '0' : '') + day + '.' +
                                (month < 10 ? '0' : '') + month + '.' +
                                year + ' | ' +
                                (hours < 10 ? '0' : '') + hours + ':' +
                                (minutes < 10 ? '0' : '') + minutes;
        
            return "(" + formattedDate + ")";
        }

        function addDateAndPageNumb(pdf_var, imgHeight_var) {
            // Add the date to the PDF
            var currentDate = getCurrentDateFormatted();
            var dateFontSize = 12;
            pdf_var.setFontSize(dateFontSize);
        
            // Add page numbers to the PDF
            var pageNumberFontSize = 12;
            pdf_var.setFontSize(pageNumberFontSize);
            var totalPages = pdf_var.getNumberOfPages();
            
            for (var i = 1; i <= totalPages; i++) {
                pdf_var.setPage(i);
        
                // Date position
                var dateX = pdf_var.internal.pageSize.getWidth() - 80;
                var dateY = pdf_var.internal.pageSize.getHeight() - 10;
                pdf_var.text(currentDate, dateX, dateY);
        
                // Page number position
                var pageText = "[Side " + i + " av " + totalPages + "]";
                var pageInfoX = pdf_var.internal.pageSize.getWidth() - 30;
                var pageInfoY = pdf_var.internal.pageSize.getHeight() - 10;
                pdf_var.text(pageText, pageInfoX, pageInfoY);
            }
        }
        


    }); // Close the document ready function
})(jQuery); // Close the jQuery wrapper