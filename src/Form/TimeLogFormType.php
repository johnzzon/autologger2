<?php

namespace App\Form;

use App\Service\Harvest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeLogFormType extends AbstractType
{

    /**
     * The harvest service.
     *
     * @var Harvest
     */
    protected $harvest;

    public function __construct(Harvest $harvest)
    {
        $this->harvest = $harvest;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $projects = $this->harvest->getProjects();
        $project_options = array_map(function($project) {
            return $project->name;
        }, $projects->toArray());
        $empty_option = [false => '- Select -'];
        $builder
            ->add('project', ChoiceType::class, [
                'choices' => array_flip($empty_option + $project_options),
            ])
            ->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
