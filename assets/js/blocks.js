(function (blocks, element, serverSideRender, components, blockEditor, data) {
  var el = element.createElement;
  var Fragment = element.Fragment;
  var ServerSideRender = serverSideRender;
  var { InspectorControls, InnerBlocks, useBlockProps } = blockEditor;
  var { useSelect } = data;

  /**
   * Parent Block: GlintLab Team Grid
   */
  blocks.registerBlockType("glintlab/team-grid", {
    title: "GlintLab Team Grid",
    icon: "groups",
    category: "widgets",
    supports: {
      align: ["wide", "full"],
    },
    attributes: {
      columns: {
        type: "string",
        default: "3",
      },
      align: {
        type: "string",
        default: "wide",
      },
    },
    edit: function (props) {
      var blockProps = useBlockProps
        ? useBlockProps({
          className:
            "glintlab-team-grid-surface glintlab-block-editor-wrapper glintlab-team-grid glintlab-team-grid--cols-" +
            props.attributes.columns,
          style: {
            "--glintlab-columns": props.attributes.columns,
          },
        })
        : {
          className:
            "glintlab-team-grid-surface glintlab-block-editor-wrapper glintlab-team-grid glintlab-team-grid--cols-" +
            props.attributes.columns,
          style: {
            "--glintlab-columns": props.attributes.columns,
          },
        };

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          { key: "inspector" },
          el(
            components.PanelBody,
            { title: "Grid Settings", initialOpen: true },
            el(components.SelectControl, {
              label: "Columns",
              value: props.attributes.columns,
              options: [
                { label: "1 Column", value: "1" },
                { label: "2 Columns", value: "2" },
                { label: "3 Columns", value: "3" },
                { label: "4 Columns", value: "4" },
              ],
              onChange: function (value) {
                props.setAttributes({ columns: value });
              },
            })
          )
        ),
        el(
          "div",
          blockProps,
          el(InnerBlocks, {
            allowedBlocks: ["glintlab/team-member"],
            template: [["glintlab/team-member"], ["glintlab/team-member"]],
            orientation: "horizontal"
          })
        )
      );
    },
    save: function () {
      return el(InnerBlocks.Content);
    },
  });

  /**
   * Child Block: GlintLab Team Member
   */
  blocks.registerBlockType("glintlab/team-member", {
    title: "Team Member",
    icon: "admin-users",
    category: "widgets",
    parent: ["glintlab/team-grid"],
    attributes: {
      memberId: {
        type: "number",
        default: 0,
      },
    },
    edit: function (props) {
      const teamMembers = useSelect((select) => {
        return select("core").getEntityRecords("postType", "glintlab_team", {
          per_page: -1,
          orderby: "title",
          order: "asc",
        });
      }, []);

      const options = [
        { label: "Select a Member", value: 0 },
        ...(teamMembers || []).map((member) => ({
          label: member.title.rendered,
          value: member.id,
        })),
      ];

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            components.PanelBody,
            { title: "Member Selection" },
            el(components.SelectControl, {
              label: "Team Member",
              value: props.attributes.memberId,
              options: options,
              onChange: function (value) {
                props.setAttributes({ memberId: parseInt(value, 10) });
              },
            })
          )
        ),
        props.attributes.memberId === 0
          ? el(
            "div",
            {
              className: "glintlab-team-member-placeholder",
            },
            el(components.SelectControl, {
              label: "Select Member to Preview",
              value: props.attributes.memberId,
              options: options,
              onChange: function (value) {
                props.setAttributes({ memberId: parseInt(value, 10) });
              },
            })
          )
          : el(
            "div",
            { className: "glintlab-child-member-preview" },
            el(ServerSideRender, {
              block: "glintlab/team-member",
              attributes: props.attributes,
            })
          )
      );
    },
    save: function () {
      return null;
    },
  });

  /**
   * Meta-syncing Header Block for Team Member Editor (Option A)
   */
  blocks.registerBlockType("glintlab/team-profile-header", {
    title: "Team Profile Details",
    icon: "admin-users",
    category: "widgets",
    attributes: {},
    edit: function (props) {
      const [meta, setMeta] = data.useEntityProp("postType", "glintlab_team", "meta");

      const role = meta["_glintlab_team_role"] || "";
      const link = meta["_glintlab_team_link_url"] || "";

      return el(
        "div",
        {
          className: "glintlab-team-profile-header-editor",
          style: {
            padding: "24px",
            background: "#16181d",
            borderRadius: "16px",
            marginBottom: "24px",
            border: "1px solid rgba(255,255,255,0.1)",
          },
        },
        el(
          "div",
          { style: { marginBottom: "16px" } },
          el("label", { style: { display: "block", color: "#6c8e98", fontSize: "12px", textTransform: "uppercase", fontWeight: "700", marginBottom: "8px" } }, "Job Title / Role"),
          el(components.TextControl, {
            value: role,
            placeholder: "e.g. Software Engineer",
            onChange: (value) => setMeta({ ...meta, _glintlab_team_role: value }),
          })
        ),
        el(
          "div",
          null,
          el("label", { style: { display: "block", color: "#6c8e98", fontSize: "12px", textTransform: "uppercase", fontWeight: "700", marginBottom: "8px" } }, "LinkedIn / Profile URL"),
          el(components.TextControl, {
            value: link,
            placeholder: "https://linkedin.com/in/...",
            onChange: (value) => setMeta({ ...meta, _glintlab_team_link_url: value }),
          })
        )
      );
    },
    save: function () {
      return null; // Meta blocks don't save content to the DB
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.serverSideRender,
  window.wp.components,
  window.wp.blockEditor,
  window.wp.data
);
