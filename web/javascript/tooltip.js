var tooltipInfo = {
    tooltipElement: false,
    sourceElement: false,
    tooltipRemoveClock: false,
    nextTooltipText: false,
    nextSourceElement: false
};

/**
 * Shows a tooltip. There will never be two tooltips at the same time on a page.
 * The tooltip will be show 10px below the source element.
 *
 * The tooltip will stay visible when the mouse hovers over the source element
 * or the tooltip. When the mouse leaves the tooltip and the source element, the
 * tooltip is removed after a short time.
 *
 * @param theSourceElement The element that was hovered over.
 * @param tooltipText The text for the tooltip.
 */
function tooltip(theSourceElement, tooltipText) {
    if (isTooltipActive()) {
        if (theSourceElement == tooltipInfo.sourceElement) {
            // This tooltip is already visible
            return;
        }

        // Initialize new tooltip when current tooltip vanishes
        // (Note that this function can only be called when the user is hovering
        // over another element, so we know that the current tooltip will
        // dissappear soon, unless the user hovers back)
        tooltipInfo.nextSourceElement = theSourceElement;
        tooltipInfo.nextTooltipText = tooltipText;
        return;
    }

    tooltipInfo.sourceElement = theSourceElement;

    // Make sure tooltip is initialized
    if (!tooltipInfo.tooltipElement) {
        tooltipInfo.tooltipElement = createTooltip();
    }

    populateTooltip(tooltipText);
}

/**
 * Updates the tooltip's text to the given text and updates the position to
 * match the position of the source element. If the tooltip is not already
 * visible, it will be made visble.
 * @param {String} tooltipText The text on the tooltip, may contain HTML markup.
 */
function populateTooltip(tooltipText) {
    // Update the text
    tooltipInfo.tooltipElement.innerHTML = tooltipText;

    // Position tooltip
    var browserWidth = getBrowserWidth();
    var tooltipWidth = tooltipInfo.tooltipElement.offsetWidth;
    var sourceElementBox = tooltipInfo.sourceElement.getBoundingClientRect();
    var tooltipX = sourceElementBox.left - 5;
    var tooltipY = sourceElementBox.bottom + 10;

    if (tooltipX + tooltipWidth > browserWidth) {
        tooltipX = browserWidth - tooltipWidth;
    }

    tooltipInfo.tooltipElement.style.position = "fixed";
    tooltipInfo.tooltipElement.style.left = tooltipX + "px";
    tooltipInfo.tooltipElement.style.top = tooltipY + "px";
    tooltipInfo.tooltipElement.style.visibility = "visible";
}

/**
 * Gets the width of the browser.
 */
function getBrowserWidth() {
    return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
}

/**
 * Gets the mouse x.
 * @param event The mouse event.
 */
function getMouseX(event) {
    return event.clientX;
}

/**
 * Gets the mouse y.
 * @param event The mouse event.
 */
function getMouseY(event) {
    return event.clientY;
}

/**
 * Creates a new tooltip. Should only be called when there is no tooltip created
 * yet.
 * @returns The tooltip.
 * @see getTooltipElement
 */
function createTooltip() {
    var tooltipElement = document.createElement('div');

    // Try to remove on move movement
    window.onmousemove = handleMouseOver;

    // Let it destroy itself by clicking
    tooltipElement.onclick = removeTooltip;

    // Set the id and add to the page
    tooltipElement.setAttribute('id', 'tooltip');
    document.body.appendChild(tooltipElement);

    return tooltipElement;
}

/**
 * Gets whether there is currently a tooltip visible.
 * @returns {Boolean} True if the tooltip is visible, false otherwise.
 */
function isTooltipActive() {
    if (!tooltipInfo.tooltipElement) {
        return false;
    }
    return tooltipInfo.tooltipElement.style.visibility === "visible";
}

/**
 * Gets whether the mouse is currently over the given element.
 * @param {Element} element The element to check.
 * @param {Number} mouseX The current mouse x.
 * @param {Number} mouseY The current mouse y.
 * @returns {Boolean} True if the mouse if over the element, false otherwise.
 */
function isMouseOver(element, mouseX, mouseY) {
    var boundingBox = element.getBoundingClientRect();
    if (mouseX < boundingBox.left || mouseX > boundingBox.right) {
        return false;
    }
    if (mouseY < boundingBox.top || mouseY > boundingBox.bottom) {
        return false;
    }
    return true;
}

function handleMouseOver(event) {
    if (!isTooltipActive()) {
        return;
    }

    var mouseX = getMouseX(event);
    var mouseY = getMouseY(event);
    if (!isMouseOver(tooltipInfo.tooltipElement, mouseX, mouseY) && !isMouseOver(tooltipInfo.sourceElement, mouseX, mouseY)) {
        // Mouse out
        if (tooltipInfo.tooltipRemoveClock === false) {
            // Prepare to remove tooltip
            tooltipInfo.tooltipRemoveClock = window.setTimeout(handleTooltipRemoval, 300);
        }
    } else {
        // Mouse over

        if (tooltipInfo.tooltipRemoveClock !== false) {
            // Used moved away with the mouse, but is now back

            // Clear the timeout
            window.clearTimeout(tooltipInfo.tooltipRemoveClock);
            tooltipInfo.tooltipRemoveClock = false;

            // Clear the next tooltip
            tooltipInfo.nextSourceElement = false;
            tooltipInfo.nextTooltipText = false;
        }
    }
}

function handleTooltipRemoval() {
    removeTooltip();

    // Check for new tooltip
    if (tooltipInfo.nextTooltipText && tooltipInfo.nextSourceElement) {
        tooltip(tooltipInfo.nextSourceElement, tooltipInfo.nextTooltipText);
        tooltipInfo.nextSourceElement = false;
        tooltipInfo.nextTooltipText = false;
    }
}

function removeTooltip() {
    tooltipInfo.tooltipElement.style.visibility = "hidden";
    tooltipInfo.tooltipRemoveClock = false;
    tooltipInfo.sourceElement = false;
}
