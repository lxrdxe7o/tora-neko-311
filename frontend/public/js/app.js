/**
 * =============================================================================
 * Quantum Airways - Frontend Application
 * =============================================================================
 * Vanilla JavaScript application for the quantum-secure booking system.
 * =============================================================================
 */

// =============================================================================
// Configuration
// =============================================================================
const API_BASE = "http://localhost:5000/api";

// =============================================================================
// State
// =============================================================================
const state = {
  flights: [],
  selectedFlight: null,
  seatMap: [],
  selectedSeat: null,
};

// =============================================================================
// DOM Elements
// =============================================================================
const elements = {
  // Flight selection
  flightSelect: document.getElementById("flightSelect"),
  flightInfo: document.getElementById("flightInfo"),
  flightRoute: document.getElementById("flightRoute"),
  flightDeparture: document.getElementById("flightDeparture"),
  flightPrice: document.getElementById("flightPrice"),
  flightAvailable: document.getElementById("flightAvailable"),

  // Seat selection
  seatMap: document.getElementById("seatMap"),
  selectedSeatInfo: document.getElementById("selectedSeatInfo"),
  selectedSeatLabel: document.getElementById("selectedSeatLabel"),
  selectedSeatClass: document.getElementById("selectedSeatClass"),

  // Booking form
  bookingForm: document.getElementById("bookingForm"),
  passengerName: document.getElementById("passengerName"),
  passportNumber: document.getElementById("passportNumber"),
  bookButton: document.getElementById("bookButton"),

  // Modal
  resultModal: document.getElementById("resultModal"),
  modalContent: document.getElementById("modalContent"),
  modalClose: document.getElementById("modalClose"),

  // Verify
  verifyBookingRef: document.getElementById("verifyBookingRef"),
  verifyButton: document.getElementById("verifyButton"),
  verifyResult: document.getElementById("verifyResult"),

  // Loading
  loadingOverlay: document.getElementById("loadingOverlay"),

  // Quantum status
  quantumStatus: document.getElementById("quantumStatus"),
};

// =============================================================================
// API Functions
// =============================================================================
async function apiRequest(endpoint, options = {}) {
  const url = `${API_BASE}/${endpoint}`;

  const defaultOptions = {
    headers: {
      "Content-Type": "application/json",
    },
  };

  const response = await fetch(url, { ...defaultOptions, ...options });
  const data = await response.json();

  if (!response.ok && !data.success) {
    throw new Error(data.error || "API request failed");
  }

  return data;
}

async function fetchFlights() {
  return apiRequest("flights");
}

async function fetchSeats(flightId) {
  return apiRequest(`seats/${flightId}`);
}

async function createBooking(
  seatId,
  passengerName,
  passportNumber,
  flightId,
  row,
  col
) {
  return apiRequest("book", {
    method: "POST",
    body: JSON.stringify({
      flight_id: flightId,
      row: row,
      col: col,
      name: passengerName,
      passport: passportNumber,
    }),
  });
}

async function verifyTicket(bookingRef) {
  return apiRequest("verify", {
    method: "POST",
    body: JSON.stringify({
      booking_ref: bookingRef,
    }),
  });
}

// =============================================================================
// UI Functions
// =============================================================================
function showLoading() {
  elements.loadingOverlay.classList.remove("hidden");
}

function hideLoading() {
  elements.loadingOverlay.classList.add("hidden");
}

function showModal() {
  elements.resultModal.classList.remove("hidden");
}

function hideModal() {
  elements.resultModal.classList.add("hidden");
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatPrice(price) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(price);
}

function updateBookButton() {
  const hasName = elements.passengerName.value.trim().length > 0;
  const hasPassport = elements.passportNumber.value.trim().length > 0;
  const hasSeat = state.selectedSeat !== null;

  elements.bookButton.disabled = !(hasName && hasPassport && hasSeat);
}

// =============================================================================
// Flight Functions
// =============================================================================
function populateFlightSelect(flights) {
  elements.flightSelect.innerHTML =
    '<option value="">-- Select a flight --</option>';

  flights.forEach((flight) => {
    const option = document.createElement("option");
    option.value = flight.id;
    option.textContent = `${flight.flight_number} | ${flight.origin} → ${
      flight.destination
    } | ${formatDate(flight.departure_time)} | ${formatPrice(flight.price)}`;
    elements.flightSelect.appendChild(option);
  });
}

function updateFlightInfo(flight) {
  if (!flight) {
    elements.flightInfo.classList.add("hidden");
    return;
  }

  elements.flightRoute.textContent = `${flight.origin} → ${flight.destination}`;
  elements.flightDeparture.textContent = formatDate(flight.departure_time);
  elements.flightPrice.textContent = formatPrice(flight.price);
  elements.flightAvailable.textContent = `${flight.available_seats} / ${flight.total_seats}`;
  elements.flightInfo.classList.remove("hidden");
}

// =============================================================================
// Seat Map Functions
// =============================================================================
function renderSeatMap(seatData) {
  const { seat_map, legend } = seatData;

  // Clear existing
  elements.seatMap.innerHTML = "";

  // Column headers
  const headerRow = document.createElement("div");
  headerRow.className = "seat-row";
  headerRow.innerHTML = `
        <div class="row-label"></div>
        <div class="seat-group">
            ${legend.columns
              .slice(0, 3)
              .map(
                (col) =>
                  `<div class="seat" style="background:transparent;border:none;cursor:default;color:var(--text-muted)">${col}</div>`
              )
              .join("")}
        </div>
        <div class="aisle"></div>
        <div class="seat-group">
            ${legend.columns
              .slice(3)
              .map(
                (col) =>
                  `<div class="seat" style="background:transparent;border:none;cursor:default;color:var(--text-muted)">${col}</div>`
              )
              .join("")}
        </div>
        <div class="row-label"></div>
    `;
  elements.seatMap.appendChild(headerRow);

  let currentClass = "";

  seat_map.forEach((row) => {
    // Add class divider and label
    if (row.class !== currentClass) {
      if (currentClass !== "") {
        const divider = document.createElement("div");
        divider.className = "class-divider";
        elements.seatMap.appendChild(divider);
      }

      const classLabel = document.createElement("div");
      classLabel.className = "class-label";
      classLabel.textContent = row.class.toUpperCase() + " CLASS";
      elements.seatMap.appendChild(classLabel);

      currentClass = row.class;
    }

    const rowDiv = document.createElement("div");
    rowDiv.className = "seat-row";

    // Row label
    const rowLabelLeft = document.createElement("div");
    rowLabelLeft.className = "row-label";
    rowLabelLeft.textContent = row.row;
    rowDiv.appendChild(rowLabelLeft);

    // Left seat group (A, B, C)
    const leftGroup = document.createElement("div");
    leftGroup.className = "seat-group";

    row.seats.slice(0, 3).forEach((seat) => {
      leftGroup.appendChild(createSeatElement(seat, row.class, row.row));
    });

    rowDiv.appendChild(leftGroup);

    // Aisle
    const aisle = document.createElement("div");
    aisle.className = "aisle";
    rowDiv.appendChild(aisle);

    // Right seat group (D, E, F)
    const rightGroup = document.createElement("div");
    rightGroup.className = "seat-group";

    row.seats.slice(3).forEach((seat) => {
      rightGroup.appendChild(createSeatElement(seat, row.class, row.row));
    });

    rowDiv.appendChild(rightGroup);

    // Row label right
    const rowLabelRight = document.createElement("div");
    rowLabelRight.className = "row-label";
    rowLabelRight.textContent = row.row;
    rowDiv.appendChild(rowLabelRight);

    elements.seatMap.appendChild(rowDiv);
  });
}

function createSeatElement(seat, seatClass, rowNum) {
  const seatDiv = document.createElement("div");
  seatDiv.className = "seat";
  seatDiv.textContent = seat.col;
  seatDiv.dataset.seatId = seat.id;
  seatDiv.dataset.label = seat.label;
  seatDiv.dataset.class = seatClass;
  seatDiv.dataset.row = rowNum;
  seatDiv.dataset.col = seat.col;

  if (seat.is_booked) {
    seatDiv.classList.add("booked");
    seatDiv.title = `Seat ${seat.label} - Booked`;
  } else {
    seatDiv.classList.add("available");
    seatDiv.title = `Seat ${seat.label} - Available (${seatClass})`;
    seatDiv.addEventListener("click", () =>
      selectSeat(seat, seatClass, rowNum)
    );
  }

  // Add class-specific styling
  if (seatClass === "first") {
    seatDiv.classList.add("first-class");
  } else if (seatClass === "business") {
    seatDiv.classList.add("business-class");
  }

  return seatDiv;
}

function selectSeat(seat, seatClass, rowNum) {
  // Deselect previous
  if (state.selectedSeat) {
    const prevSeat = document.querySelector(
      `.seat[data-seat-id="${state.selectedSeat.id}"]`
    );
    if (prevSeat) {
      prevSeat.classList.remove("selected");
      prevSeat.classList.add("available");
    }
  }

  // Select new - include row and col for booking API
  state.selectedSeat = {
    ...seat,
    class: seatClass,
    row: rowNum,
    col: seat.col,
  };

  const seatElement = document.querySelector(
    `.seat[data-seat-id="${seat.id}"]`
  );
  if (seatElement) {
    seatElement.classList.remove("available");
    seatElement.classList.add("selected");
  }

  // Update UI
  elements.selectedSeatLabel.textContent = seat.label;
  elements.selectedSeatClass.textContent = `(${seatClass})`;
  elements.selectedSeatInfo.classList.remove("hidden");

  updateBookButton();
}

// =============================================================================
// Booking Functions
// =============================================================================
function renderBookingResult(result) {
  const { booking, quantum_security } = result;

  const mockWarning = quantum_security.mock_mode
    ? `<div class="mock-warning">
               <span>&#9888;</span>
               <span>MOCK MODE - Using simulated PQC (liboqs not installed)</span>
           </div>`
    : "";

  elements.modalContent.innerHTML = `
        <div class="booking-result">
            ${mockWarning}
            
            <div class="booking-ref-display">
                <div class="label">YOUR BOOKING REFERENCE</div>
                <div class="ref">${booking.booking_ref}</div>
            </div>
            
            <div class="result-section">
                <h4>FLIGHT DETAILS</h4>
                <div class="result-grid">
                    <div class="result-item">
                        <div class="label">Flight</div>
                        <div class="value">${booking.flight.number}</div>
                    </div>
                    <div class="result-item">
                        <div class="label">Route</div>
                        <div class="value">${booking.flight.origin} → ${
    booking.flight.destination
  }</div>
                    </div>
                    <div class="result-item">
                        <div class="label">Departure</div>
                        <div class="value">${formatDate(
                          booking.flight.departure
                        )}</div>
                    </div>
                    <div class="result-item">
                        <div class="label">Seat</div>
                        <div class="value">${booking.seat.label} (${
    booking.seat.class
  })</div>
                    </div>
                </div>
            </div>
            
            <div class="result-section">
                <h4>PASSENGER</h4>
                <div class="result-item">
                    <div class="value">${booking.passenger_name}</div>
                </div>
            </div>
            
            <div class="result-section">
                <h4>QUANTUM SECURITY</h4>
                <div class="result-grid">
                    <div class="result-item">
                        <div class="label">Signature Algorithm</div>
                        <div class="value">${
                          quantum_security.signature.algorithm
                        }</div>
                    </div>
                    <div class="result-item">
                        <div class="label">Encryption Algorithm</div>
                        <div class="value">${
                          quantum_security.encryption.algorithm
                        }</div>
                    </div>
                </div>
                <div class="result-item" style="margin-top: 1rem;">
                    <div class="label">Digital Signature Preview</div>
                    <div class="signature-preview">${
                      quantum_security.signature.preview
                    }</div>
                </div>
            </div>
        </div>
    `;
}

function renderVerifyResult(result) {
  const isValid = result.verified;

  elements.verifyResult.className = `verify-result ${
    isValid ? "success" : "failure"
  }`;

  if (isValid) {
    const ticket = result.ticket;
    elements.verifyResult.innerHTML = `
            <h4>&#9989; SIGNATURE VERIFIED</h4>
            <p>${result.message}</p>
            <div class="result-grid" style="margin-top: 1rem;">
                <div class="result-item">
                    <div class="label">Flight</div>
                    <div class="value">${ticket.flight_number}</div>
                </div>
                <div class="result-item">
                    <div class="label">Passenger</div>
                    <div class="value">${ticket.passenger}</div>
                </div>
                <div class="result-item">
                    <div class="label">Seat</div>
                    <div class="value">${ticket.seat}</div>
                </div>
                <div class="result-item">
                    <div class="label">Algorithm</div>
                    <div class="value">${
                      result.security.signature_algorithm
                    }</div>
                </div>
            </div>
            ${
              result.security.mock_mode
                ? '<p style="margin-top:1rem;color:var(--warning);font-size:0.75rem;">&#9888; Verified using mock mode (HMAC-SHA512)</p>'
                : ""
            }
        `;
  } else {
    elements.verifyResult.innerHTML = `
            <h4>&#10060; VERIFICATION FAILED</h4>
            <p>${result.message}</p>
            <p style="margin-top:1rem;color:var(--error);font-size:0.85rem;">
                This ticket may have been tampered with or the signature is invalid.
            </p>
        `;
  }

  elements.verifyResult.classList.remove("hidden");
}

// =============================================================================
// Event Handlers
// =============================================================================
async function handleFlightChange(e) {
  const flightId = e.target.value;

  if (!flightId) {
    state.selectedFlight = null;
    state.seatMap = [];
    state.selectedSeat = null;
    updateFlightInfo(null);
    elements.seatMap.innerHTML =
      '<div class="seat-placeholder">Select a flight to view seat map</div>';
    elements.selectedSeatInfo.classList.add("hidden");
    updateBookButton();
    return;
  }

  try {
    showLoading();
    const response = await fetchSeats(flightId);

    state.selectedFlight = response.data.flight;
    state.seatMap = response.data.seat_map;
    state.selectedSeat = null;

    updateFlightInfo({
      ...state.selectedFlight,
      available_seats: response.data.statistics.available_seats,
      total_seats: response.data.statistics.total_seats,
    });

    renderSeatMap(response.data);
    elements.selectedSeatInfo.classList.add("hidden");
    updateBookButton();
  } catch (error) {
    console.error("Failed to fetch seats:", error);
    alert("Failed to load seat map. Please try again.");
  } finally {
    hideLoading();
  }
}

async function handleBookingSubmit(e) {
  e.preventDefault();

  if (!state.selectedSeat) {
    alert("Please select a seat first.");
    return;
  }

  const passengerName = elements.passengerName.value.trim();
  const passportNumber = elements.passportNumber.value.trim();

  if (!passengerName || !passportNumber) {
    alert("Please fill in all passenger details.");
    return;
  }

  try {
    showLoading();
    const result = await createBooking(
      state.selectedSeat.id,
      passengerName,
      passportNumber,
      state.selectedFlight.id,
      state.selectedSeat.row,
      state.selectedSeat.col
    );

    renderBookingResult(result);
    showModal();

    // Refresh seat map
    if (state.selectedFlight) {
      const response = await fetchSeats(state.selectedFlight.id);
      state.seatMap = response.data.seat_map;
      renderSeatMap(response.data);

      // Update available seats count
      updateFlightInfo({
        ...state.selectedFlight,
        available_seats: response.data.statistics.available_seats,
        total_seats: response.data.statistics.total_seats,
      });
    }

    // Reset form
    elements.passengerName.value = "";
    elements.passportNumber.value = "";
    state.selectedSeat = null;
    elements.selectedSeatInfo.classList.add("hidden");
    updateBookButton();
  } catch (error) {
    console.error("Booking failed:", error);
    alert(`Booking failed: ${error.message}`);
  } finally {
    hideLoading();
  }
}

async function handleVerify() {
  const bookingRef = elements.verifyBookingRef.value.trim().toUpperCase();

  if (!bookingRef) {
    alert("Please enter a booking reference.");
    return;
  }

  try {
    showLoading();
    const result = await verifyTicket(bookingRef);
    renderVerifyResult(result.data || result);
  } catch (error) {
    console.error("Verification failed:", error);
    elements.verifyResult.className = "verify-result failure";
    elements.verifyResult.innerHTML = `
            <h4>&#10060; VERIFICATION ERROR</h4>
            <p>${error.message}</p>
        `;
    elements.verifyResult.classList.remove("hidden");
  } finally {
    hideLoading();
  }
}

// =============================================================================
// Initialization
// =============================================================================
async function init() {
  // Bind event listeners
  elements.flightSelect.addEventListener("change", handleFlightChange);
  elements.bookingForm.addEventListener("submit", handleBookingSubmit);
  elements.passengerName.addEventListener("input", updateBookButton);
  elements.passportNumber.addEventListener("input", updateBookButton);
  elements.modalClose.addEventListener("click", hideModal);
  elements.resultModal.addEventListener("click", (e) => {
    if (e.target === elements.resultModal) hideModal();
  });
  elements.verifyButton.addEventListener("click", handleVerify);
  elements.verifyBookingRef.addEventListener("keypress", (e) => {
    if (e.key === "Enter") handleVerify();
  });

  // Close modal on Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") hideModal();
  });

  // Load flights
  try {
    showLoading();
    const response = await fetchFlights();
    state.flights = response.data.flights;
    populateFlightSelect(state.flights);
  } catch (error) {
    console.error("Failed to load flights:", error);
    elements.flightSelect.innerHTML =
      '<option value="">Failed to load flights</option>';

    // Update quantum status to show error
    elements.quantumStatus.innerHTML = `
            <span class="status-dot" style="background: var(--error); box-shadow: 0 0 10px var(--error);"></span>
            <span class="status-text" style="color: var(--error);">CONNECTION ERROR</span>
        `;
  } finally {
    hideLoading();
  }
}

// Start the application
document.addEventListener("DOMContentLoaded", init);
