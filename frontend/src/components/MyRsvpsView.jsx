import React from "react";
import { CalendarDays, RefreshCw } from "lucide-react";
import EventCard from "./EventCard";

export default function MyRsvpsView({
  events,
  loading,
  rsvpBusyEventId,
  onRefresh,
  onRsvp,
  onBrowse,
  prettyDate,
}) {
  return (
    <section className="events-section" id="my-rsvps">
      <div className="section-heading">
        <div>
          <div className="eyebrow muted">YOUR CAMPUS PLANS</div>
          <h2>
            My <span>RSVPs.</span>
          </h2>
          <p>Events you have registered to attend.</p>
        </div>
        <button className="btn btn-outline" onClick={onRefresh}>
          <RefreshCw size={15} /> Refresh
        </button>
      </div>
      {loading ? (
        <div className="loading-line">
          <span /> Loading your RSVPs...
        </div>
      ) : events.length === 0 ? (
        <div className="empty-state">
          <div className="empty-icon">
            <CalendarDays size={27} />
          </div>
          <h3>No RSVPs yet</h3>
          <p>Choose an event and RSVP to see it here.</p>
          <button className="btn btn-primary" onClick={onBrowse}>
            Browse events
          </button>
        </div>
      ) : (
        <div className="event-grid">
          {events.map((event) => (
            <EventCard
              key={event.id}
              event={event}
              prettyDate={prettyDate}
              isRsvped
              isBusy={rsvpBusyEventId === event.id}
              onRsvp={onRsvp}
              myRsvp
            />
          ))}
        </div>
      )}
    </section>
  );
}
