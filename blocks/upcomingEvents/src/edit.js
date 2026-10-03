import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import "./editor.scss";
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect } from "@wordpress/element";
import {
  TextControl,
  Panel,
  PanelBody,
  CheckboxControl,
  __experimentalNumberControl as NumberControl,
} from "@wordpress/components";

const Edit = ({ attributes, setAttributes }) => {
  const { items, months, categories: rawCategories, title } = attributes;

  // Ensure categories is always a valid object, even if Gutenberg passes a JSON string
  const categories =
    typeof rawCategories === "string"
      ? (() => {
          try {
            return JSON.parse(rawCategories);
          } catch (e) {
            return {};
          }
        })()
      : rawCategories || {};

  const [events, storeEvents] = useState([]);
  const [eventCats, setEventCats] = useState([]);

  useEffect(() => {
    apiFetch({ path: "/wp/v2/events" })
      .then((res) => {
        if (Array.isArray(res)) {
          setEventCats(res);
        }
      })
      .catch((err) => console.error(err));
  }, []);

  const onCatChanged = (checked, id) => {
    // Immutable update on a guaranteed object
    const updatedCategories = {
      ...categories,
      [id]: checked,
    };

    setAttributes({ categories: updatedCategories });
  };

  const fetchEvents = async () => {
    let param = "";

    if (items !== undefined) {
      param += "?items=" + items;
    }

    if (months !== undefined) {
      param += (param === "" ? "?" : "&") + "months=" + months;
    }

    if (categories && Object.keys(categories).length > 0) {
      param += (param === "" ? "?" : "&") + "categories=";
      const catIds = Object.keys(categories).filter((key) => categories[key]);
      param += catIds.join(",");
    }

    try {
      let fetchedEvents = await apiFetch({
        path: `tsjippy/v2/events/upcoming_events${param}`,
      });
      storeEvents(Array.isArray(fetchedEvents) ? fetchedEvents : []);
    } catch (e) {
      storeEvents([]);
    }
  };

  useEffect(() => {
    fetchEvents();
  }, [items, months, rawCategories]);

  const buildHtml = () => {
    if (!events || events.length === 0) {
      return <p>No events found!</p>;
    }

    return events.map((event, index) => {
      const uniqueKey = event.id ?? event.slug ?? `event-${index}`;

      return (
        <article className="event-article" key={uniqueKey}>
          <div className="event-wrapper">
            <div className="event-date">
              <span>{event.day}</span> {event.month}
            </div>
            <div>
              <h4 className="event-title">
                <a href={event.url}>{event.title}</a>
              </h4>
              <div className="event-detail">{event.time}</div>
            </div>
          </div>
        </article>
      );
    });
  };

  return (
    <>
      <InspectorControls>
        <Panel>
          <PanelBody>
            <TextControl
              label="Block title"
              value={title}
              onChange={(val) => setAttributes({ title: val })}
            />
            Select a category you want to exclude from the list:
            {eventCats.map((c, index) => (
              <CheckboxControl
                key={c.id ?? `cat-${index}`}
                label={c.name}
                onChange={(checked) => onCatChanged(checked, c.id)}
                checked={!!categories[c.id]}
              />
            ))}
            <NumberControl
              label={__("Select the maximum amount of events", "tsjippy")}
              value={items || 10}
              onChange={(val) => setAttributes({ items: parseInt(val, 10) || 0 })}
              min={1}
              max={20}
            />
            <NumberControl
              label={__(
                "Select the range in months we will retrieve",
                "tsjippy"
              )}
              value={months || 2}
              onChange={(val) => setAttributes({ months: parseInt(val, 10) || 0 })}
              min={1}
              max={12}
            />
          </PanelBody>
        </Panel>
      </InspectorControls>
      <div {...useBlockProps()}>
        <aside className="event">
          <h4 className="title">{title}</h4>
          <div className="upcomingevents_wrapper">{buildHtml()}</div>
          <a className="calendar button tsjippy" href="./events">
            Calendar
          </a>
        </aside>
      </div>
    </>
  );
};

export default Edit;