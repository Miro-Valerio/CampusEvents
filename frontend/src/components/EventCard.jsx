import React from "react";
import {ArrowUpRight,Bookmark,CalendarDays,MapPin,Users,X
} from "lucide-react";

export default function EventCard({
  event,
  prettyDate,
  isRsvped,
  isBusy,
  onRsvp,
  onSave,
  onOpen,
  onOrganizer,
  myRsvp = false,
}) {
  const isFull = !isRsvped && event.registered_count >= event.capacity;

  return (
    <article className="event-card">
      <div
        className="poster"
        style={{
          backgroundImage: `linear-gradient(180deg, transparent 30%, rgba(11,28,55,.72)), url('${event.poster_url || ""}')`,
        }}
      >
        <span className="category-tag">{event.category}</span>
        {!myRsvp && (
          <button
            className="save-btn"
            title="Save event"
            onClick={() => onSave(event)}
          >
            <Bookmark size={17} />
          </button>
        )}
        <span className="poster-date">
          <CalendarDays size={14} />
          {new Date(event.starts_at).toLocaleDateString([], {
            month: "short",
            day: "numeric",
          })}
        </span>
      </div>
      <div className="event-card-body">
        <div className="organizer-line">
          {event.organizer?.name || "SPC Organizer"}
        </div>
        <h3>{event.title}</h3>
        <div className="event-meta">
          <span>
            <CalendarDays size={15} />
            {prettyDate(event.starts_at)}
          </span>
          <span>
            <MapPin size={15} />
            {event.venue}
          </span>
        </div>
        {myRsvp ? (
          <div className="rsvp-seat-number">
            Your seat: <strong>#{event.seat_number}</strong>
          </div>
        ) : (
          <div className="seat-row">
            <div className="seat-copy">
              <Users size={15} />
              <span>
                <b>{event.registered_count}</b> of {event.capacity} going
              </span>
            </div>
            <div className="seat-track">
              <span
                style={{
                  width: `${Math.min(100, (event.registered_count / event.capacity) * 100)}%`,
                }}
              />
            </div>
          </div>
        )}
        {myRsvp && (
          <div className="seat-copy">
            <Users size={15} />
            <span>
              <b>{event.registered_count}</b> of {event.capacity} going
            </span>
          </div>
        )}
        {!myRsvp && (
          <button className="details-link" onClick={() => onOpen(event)}>
            View event details <ArrowUpRight size={16} />
          </button>
        )}
        <button
          className={`btn ${myRsvp ? "btn-outline" : "btn-primary"} full-btn rsvp-button`}
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
        {!myRsvp && (
          <button
            className="organizer-link"
            onClick={() => onOrganizer(event.organizer_id)}
          >
            More from {event.organizer?.name || "organizer"}
          </button>
        )}
      </div>
    </article>
  );
}
