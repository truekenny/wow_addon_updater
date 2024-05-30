@cd %~dp0
@c:\php\php index.php


@for %%x in (%*) do @(
  if "%%x" == "--no-pause" (
    goto END
  )
)

@pause

:END
