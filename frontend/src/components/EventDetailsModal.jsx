import React from "react";
import { Bookmark, CalendarDays, MapPin, Users, X } from "lucide-react";

export default function EventDetailsModal({
  event,
  isRsvped,
  isBusy,
  onClose,
  onRsvp,
  onSave,
  onOrganizer,
  prettyDate,
}) {
  if (!event) return null;

  const isFull = !isRsvped && event.registered_count >= event.capacity;

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div
        className="modal event-modal"
        onClick={(click) => click.stopPropagation()}
      >
        <button className="modal-close" onClick={onClose}>
          <X />
        </button>
        <div
          className="modal-poster"
          style={{
            backgroundImage: `linear-gradient(0deg, rgba(12,31,61,.5), transparent), url('${event.poster_url || ""}')`,
          }}
        />
        <div className="modal-content">
          <span className="category-tag static-tag">{event.category}</span>
          <h2>{event.title}</h2>
          <p>{event.description}</p>
          <div className="detail-row">
            <CalendarDays />
            <span>
              <b>Date &amp; time</b>
              {prettyDate(event.starts_at)}
              {event.ends_at ? ` – ${prettyDate(event.ends_at)}` : ""}
            </span>
          </div>
          <div className="detail-row">
            <MapPin />
            <span>
              <b>Venue</b>
              {event.venue}
            </span>
          </div>
          <div className="detail-row">
            <Users />
            <span>
              <b>Seats</b>
              {event.registered_count} of {event.capacity} going
            </span>
          </div>
          <div className="rules-box">
            <b>Event rules</b>
            <p>
              {event.rules ||
                "Please follow organizer instructions and bring your student ID."}
            </p>
          </div>
          <button
            className="btn btn-primary full-btn rsvp-button"
            disabled={isBusy || isFull}
            onClick={() => onRsvp(event)}
          >
            {isBusy
              ? "Updating…"
              : isRsvped
                ? "Cancel RSVP"
                : isFull
                  ? "Event full"
                  : "RSVP · Going"}
          </button>
          <button
            className="btn btn-outline full-btn"
            onClick={() => onSave(event)}
          >
            <Bookmark size={16} /> Save this event
          </button>
          <button
            className="organizer-link modal-org"
            onClick={() => onOrganizer(event.organizer_id)}
          >
            View organizer: {event.organizer?.name}
          </button>
        </div>
      </div>
    </div>
  );
}
