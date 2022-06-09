##@ [Development]

.PHONY:npm commit _clean_working_dir

npm: ## Run an npm script. Usage: make npm ARGS="run dev".
	$(MAYBE_RUN_NODE_IN_DOCKER) npm ${ARGS}

commit:  ## Launch the interactive commit tool (node required locally).
	@if ! command -v npm &> /dev/null; \
    then \
        printf "This make target can currently not run in docker and requires node + npm on your local machine.\n";\
        exit 1;\
    fi
	npm run commit;


