<?php

declare(strict_types=1);
/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\Entity;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
enum RoleUser: string
{
    case ROLE_SUPER_ADMIN = "ROLE_SUPER_ADMIN";

    case ROLE_FOUNDER = "ROLE_FOUNDER";

    case ROLE_DIRECTOR = "ROLE_DIRECTOR";

    case ROLE_EDITORIAL_MGR = "ROLE_EDITORIAL_MGR";

    case ROLE_EDITOR_SENIOR = "ROLE_EDITOR_SENIOR";

    case ROLE_EDITOR_JUNIOR ="ROLE_EDITOR_JUNIOR" ;

    case ROLE_CONTENT_MGR = "ROLE_CONTENT_MGR" ;

    case ROLE_MODERATION_MGR = "ROLE_MODERATION_MGR" ;

    case ROLE_MODERATEUR = "ROLE_MODERATEUR" ;

    case ROLE_TECH_MGR ="ROLE_TECH_MGR" ;

    case ROLE_REDACTEUR = "ROLE_REDACTEUR" ;

    case ROLE_REVIEWER ="ROLE_REVIEWER" ;

    case ROLE_DEV = "ROLE_DEV" ;

}
