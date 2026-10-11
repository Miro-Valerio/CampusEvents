import React from "react";
import { ArrowUpRight, Users, X } from "lucide-react";

export default function OrganizerModal({
  organizer,
  onClose,
  onOpenEvent,
  prettyDate,
}) {
  if (!organizer) return null;

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div
        className="modal organizer-modal"
        onClick={(click) => click.stopPropagation()}
      >
        <button className="modal-close" onClick={onClose}>
          <X />
        </button>
        <div className="organizer-cover">
          <Users size={35} />
        </div>
        <div className="modal-content">
          <div className="eyebrow muted">EVENT ORGANIZER</div>
          <h2>{organizer.name}</h2>
          <p>{organizer.description}</p>
          <h3>Upcoming events</h3>
          {organizer.events?.length ? (
            organizer.events.map((event) => (
              <button
                className="organizer-event"
                key={event.id}
                onClick={() => onOpenEvent(event)}
              >
                <span>
                  <b>{event.title}</b>
                  <small>
                    {prettyDate(event.starts_at)} · {event.venue}
                  </small>
                </span>
                <ArrowUpRight />
              </button>
            ))
          ) : (
            <p>No upcoming events from this organizer.</p>
          )}
        </div>
      </div>
    </div>
  );
}
